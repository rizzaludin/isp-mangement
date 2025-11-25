import routeros_api
from loguru import logger
from typing import Dict, Any


class MikrotikWorker:
    """MikroTik Router Management Worker"""
    
    def __init__(self):
        logger.info("MikroTik Worker initialized")
    
    def _connect(self, router_config: Dict[str, Any]):
        """Establish connection to MikroTik router"""
        try:
            connection = routeros_api.RouterOsApiPool(
                router_config['ip_address'],
                username=router_config['api_user'],
                password=router_config['api_password'],
                port=router_config.get('api_port', 8728),
                use_ssl=False,
                plaintext_login=True
            )
            return connection
        except Exception as e:
            logger.error(f"Failed to connect to MikroTik {router_config['ip_address']}: {str(e)}")
            raise
    
    def provision(self, data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Provision a new PPPoE user on MikroTik
        
        Args:
            data: {
                'router': {...},
                'username': 'user@pppoe',
                'password': 'password',
                'service_id': 1,
                'rate_limit': '10M/10M'
            }
        """
        try:
            router = data['router']
            username = data['username']
            password = data['password']
            rate_limit = data.get('rate_limit', '1M/1M')
            
            connection = self._connect(router)
            api = connection.get_api()
            
            # Add PPP Secret
            ppp_resource = api.get_resource('/ppp/secret')
            ppp_resource.add(
                name=username,
                password=password,
                service='pppoe',
                profile='default',
                comment=f'ISP Management - Auto-provisioned'
            )
            
            # Add Simple Queue for bandwidth limiting
            queue_resource = api.get_resource('/queue/simple')
            queue_resource.add(
                name=f'queue-{username}',
                target=username,
                max_limit=rate_limit,
                comment=f'ISP Management - {username}'
            )
            
            connection.disconnect()
            
            logger.info(f"Successfully provisioned {username} on {router['ip_address']}")
            return {
                'success': True,
                'message': f'User {username} provisioned successfully',
                'username': username
            }
            
        except Exception as e:
            logger.error(f"Failed to provision user: {str(e)}")
            return {
                'success': False,
                'error': str(e)
            }
    
    def suspend(self, data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Suspend a PPPoE user (disable or remove)
        
        Args:
            data: {
                'router': {...},
                'username': 'user@pppoe'
            }
        """
        try:
            router = data['router']
            username = data['username']
            
            connection = self._connect(router)
            api = connection.get_api()
            
            # Disable PPP Secret
            ppp_resource = api.get_resource('/ppp/secret')
            secrets = ppp_resource.get(name=username)
            
            if secrets:
                secret = secrets[0]
                ppp_resource.set(id=secret['id'], disabled='yes')
                logger.info(f"Suspended user {username} on {router['ip_address']}")
            
            # Remove from Simple Queue
            queue_resource = api.get_resource('/queue/simple')
            queues = queue_resource.get(name=f'queue-{username}')
            
            if queues:
                queue = queues[0]
                queue_resource.remove(id=queue['id'])
            
            connection.disconnect()
            
            return {
                'success': True,
                'message': f'User {username} suspended successfully'
            }
            
        except Exception as e:
            logger.error(f"Failed to suspend user: {str(e)}")
            return {
                'success': False,
                'error': str(e)
            }
    
    def unsuspend(self, data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Unsuspend a PPPoE user (re-enable and restore queue)
        
        Args:
            data: {
                'router': {...},
                'username': 'user@pppoe',
                'rate_limit': '10M/10M'
            }
        """
        try:
            router = data['router']
            username = data['username']
            rate_limit = data.get('rate_limit', '1M/1M')
            
            connection = self._connect(router)
            api = connection.get_api()
            
            # Enable PPP Secret
            ppp_resource = api.get_resource('/ppp/secret')
            secrets = ppp_resource.get(name=username)
            
            if secrets:
                secret = secrets[0]
                ppp_resource.set(id=secret['id'], disabled='no')
                logger.info(f"Unsuspended user {username} on {router['ip_address']}")
            
            # Re-add Simple Queue
            queue_resource = api.get_resource('/queue/simple')
            queues = queue_resource.get(name=f'queue-{username}')
            
            if not queues:
                queue_resource.add(
                    name=f'queue-{username}',
                    target=username,
                    max_limit=rate_limit,
                    comment=f'ISP Management - {username}'
                )
            
            connection.disconnect()
            
            return {
                'success': True,
                'message': f'User {username} unsuspended successfully'
            }
            
        except Exception as e:
            logger.error(f"Failed to unsuspend user: {str(e)}")
            return {
                'success': False,
                'error': str(e)
            }
    
    def disconnect_session(self, data: Dict[str, Any]) -> Dict[str, Any]:
        """
        Disconnect an active PPPoE session
        
        Args:
            data: {
                'router': {...},
                'username': 'user@pppoe'
            }
        """
        try:
            router = data['router']
            username = data['username']
            
            connection = self._connect(router)
            api = connection.get_api()
            
            # Find and disconnect active PPP session
            active_resource = api.get_resource('/ppp/active')
            sessions = active_resource.get(name=username)
            
            if sessions:
                for session in sessions:
                    active_resource.remove(id=session['id'])
                logger.info(f"Disconnected session for {username} on {router['ip_address']}")
            
            connection.disconnect()
            
            return {
                'success': True,
                'message': f'Session for {username} disconnected successfully'
            }
            
        except Exception as e:
            logger.error(f"Failed to disconnect session: {str(e)}")
            return {
                'success': False,
                'error': str(e)
            }
    
    def get_active_sessions(self, router_config: Dict[str, Any]) -> Dict[str, Any]:
        """Get all active PPPoE sessions from router"""
        try:
            connection = self._connect(router_config)
            api = connection.get_api()
            
            active_resource = api.get_resource('/ppp/active')
            sessions = active_resource.get()
            
            connection.disconnect()
            
            return {
                'success': True,
                'sessions': sessions
            }
            
        except Exception as e:
            logger.error(f"Failed to get active sessions: {str(e)}")
            return {
                'success': False,
                'error': str(e)
            }
