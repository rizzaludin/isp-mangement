import paramiko
from loguru import logger
from typing import Dict, Any
import re


class OLTWorker:
    """OLT (Optical Line Terminal) Management Worker - Huawei MA5800"""
    
    def __init__(self):
        logger.info("OLT Worker initialized")
    
    def _connect(self, olt_config: Dict[str, Any]):
        """Establish SSH connection to OLT"""
        try:
            client = paramiko.SSHClient()
            client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
            
            client.connect(
                hostname=olt_config['ip_address'],
                port=olt_config.get('mgmt_port', 22),
                username=olt_config['mgmt_user'],
                password=olt_config['mgmt_password'],
                timeout=10
            )
            
            return client
            
        except Exception as e:
            logger.error(f"Failed to connect to OLT {olt_config['ip_address']}: {str(e)}")
            raise
    
    def _execute_command(self, client, command: str, wait_time: float = 1.0) -> str:
        """Execute command on OLT and return output"""
        try:
            shell = client.invoke_shell()
            shell.send(command + '\n')
            
            import time
            time.sleep(wait_time)
            
            output = ""
            while shell.recv_ready():
                output += shell.recv(1024).decode('utf-8')
            
            return output
            
        except Exception as e:
            logger.error(f"Failed to execute command: {str(e)}")
            raise
    
    def provision_onu(self, data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Provision a new ONU on Huawei OLT
        
        Args:
            data: {
                'olt': {...},
                'serial_number': 'HWTC12345678',
                'pon_port': '0/1/1',
                'onu_index': 1,
                'vlan_id': 100,
                'bandwidth_profile': 'PROFILE-10M'
            }
        """
        try:
            olt = data['olt']
            serial_number = data['serial_number']
            pon_port = data['pon_port']
            onu_index = data['onu_index']
            vlan_id = data.get('vlan_id', 100)
            
            client = self._connect(olt)
            
            # Huawei OLT commands
            commands = [
                'enable',
                'config',
                f'interface gpon {pon_port}',
                f'ont add {onu_index} sn-auth {serial_number} omci ont-lineprofile-id 1 ont-srvprofile-id 1',
                'quit',
                f'service-port vlan {vlan_id} gpon {pon_port} ont {onu_index} gemport 1 multi-service user-vlan {vlan_id}',
                'quit',
                'quit'
            ]
            
            output = ""
            for cmd in commands:
                output += self._execute_command(client, cmd)
                logger.debug(f"Executed: {cmd}")
            
            client.close()
            
            logger.info(f"Successfully provisioned ONU {serial_number} on {olt['ip_address']}")
            return {
                'success': True,
                'message': f'ONU {serial_number} provisioned successfully',
                'serial_number': serial_number,
                'output': output
            }
            
        except Exception as e:
            logger.error(f"Failed to provision ONU: {str(e)}")
            return {
                'success': False,
                'error': str(e)
            }
    
    def reset_onu(self, data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Reset/reboot an ONU
        
        Args:
            data: {
                'olt': {...},
                'pon_port': '0/1/1',
                'onu_index': 1
            }
        """
        try:
            olt = data['olt']
            pon_port = data['pon_port']
            onu_index = data['onu_index']
            
            client = self._connect(olt)
            
            commands = [
                'enable',
                'config',
                f'interface gpon {pon_port}',
                f'ont reset {onu_index}',
                'quit',
                'quit'
            ]
            
            output = ""
            for cmd in commands:
                output += self._execute_command(client, cmd)
            
            client.close()
            
            logger.info(f"Successfully reset ONU on port {pon_port} index {onu_index}")
            return {
                'success': True,
                'message': f'ONU reset successfully',
                'output': output
            }
            
        except Exception as e:
            logger.error(f"Failed to reset ONU: {str(e)}")
            return {
                'success': False,
                'error': str(e)
            }
    
    def get_onu_status(self, data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Get ONU status including optical power
        
        Args:
            data: {
                'olt': {...},
                'pon_port': '0/1/1',
                'onu_index': 1
            }
        """
        try:
            olt = data['olt']
            pon_port = data['pon_port']
            onu_index = data['onu_index']
            
            client = self._connect(olt)
            
            # Get ONU optical info
            command = f'display ont optical-info {pon_port} {onu_index}'
            output = self._execute_command(client, command, wait_time=2.0)
            
            client.close()
            
            # Parse output for Rx/Tx power
            rx_power = None
            tx_power = None
            
            rx_match = re.search(r'Rx optical power.*?:.*?([-\d.]+)', output)
            tx_match = re.search(r'Tx optical power.*?:.*?([-\d.]+)', output)
            
            if rx_match:
                rx_power = float(rx_match.group(1))
            if tx_match:
                tx_power = float(tx_match.group(1))
            
            logger.info(f"Retrieved ONU status for {pon_port}/{onu_index}")
            return {
                'success': True,
                'status': {
                    'pon_port': pon_port,
                    'onu_index': onu_index,
                    'signal_rx': rx_power,
                    'signal_tx': tx_power,
                    'raw_output': output
                }
            }
            
        except Exception as e:
            logger.error(f"Failed to get ONU status: {str(e)}")
            return {
                'success': False,
                'error': str(e)
            }
    
    def unconfigure_onu(self, data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Remove ONU configuration from OLT
        
        Args:
            data: {
                'olt': {...},
                'pon_port': '0/1/1',
                'onu_index': 1
            }
        """
        try:
            olt = data['olt']
            pon_port = data['pon_port']
            onu_index = data['onu_index']
            
            client = self._connect(olt)
            
            commands = [
                'enable',
                'config',
                f'interface gpon {pon_port}',
                f'ont delete {onu_index}',
                'quit',
                'quit'
            ]
            
            output = ""
            for cmd in commands:
                output += self._execute_command(client, cmd)
            
            client.close()
            
            logger.info(f"Successfully removed ONU on port {pon_port} index {onu_index}")
            return {
                'success': True,
                'message': f'ONU removed successfully',
                'output': output
            }
            
        except Exception as e:
            logger.error(f"Failed to remove ONU: {str(e)}")
            return {
                'success': False,
                'error': str(e)
            }
