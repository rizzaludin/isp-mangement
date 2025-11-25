import time
import json
import redis
from loguru import logger
from config import settings
from workers.mikrotik_worker import MikrotikWorker
from workers.olt_worker import OLTWorker

# Configure logger
logger.add("logs/worker_{time}.log", rotation="1 day", retention="30 days", level=settings.log_level)

class NetworkWorker:
    def __init__(self):
        self.redis_client = redis.Redis(
            host=settings.redis_host,
            port=settings.redis_port,
            db=settings.redis_db,
            decode_responses=True
        )
        self.mikrotik_worker = MikrotikWorker()
        self.olt_worker = OLTWorker()
        
        logger.info("Network Worker initialized")
    
    def process_job(self, job_data: dict):
        """Process a job from the queue"""
        try:
            job_type = job_data.get('type')
            data = job_data.get('data', {})
            
            logger.info(f"Processing job: {job_type}")
            
            if job_type == 'mikrotik.provision':
                result = self.mikrotik_worker.provision(data)
            elif job_type == 'mikrotik.suspend':
                result = self.mikrotik_worker.suspend(data)
            elif job_type == 'mikrotik.unsuspend':
                result = self.mikrotik_worker.unsuspend(data)
            elif job_type == 'mikrotik.disconnect':
                result = self.mikrotik_worker.disconnect_session(data)
            elif job_type == 'olt.provision':
                result = self.olt_worker.provision_onu(data)
            elif job_type == 'olt.reset':
                result = self.olt_worker.reset_onu(data)
            elif job_type == 'olt.status':
                result = self.olt_worker.get_onu_status(data)
            else:
                logger.warning(f"Unknown job type: {job_type}")
                return {'success': False, 'error': f'Unknown job type: {job_type}'}
            
            logger.info(f"Job {job_type} completed successfully")
            return result
            
        except Exception as e:
            logger.error(f"Error processing job: {str(e)}")
            return {'success': False, 'error': str(e)}
    
    def run(self):
        """Main worker loop"""
        logger.info("Worker started, waiting for jobs...")
        
        while True:
            try:
                # Blocking pop from Redis list (queue)
                job = self.redis_client.blpop('network:jobs', timeout=5)
                
                if job:
                    queue_name, job_json = job
                    job_data = json.loads(job_json)
                    
                    result = self.process_job(job_data)
                    
                    # Store result if job has callback
                    if 'callback_key' in job_data:
                        self.redis_client.setex(
                            job_data['callback_key'],
                            3600,  # 1 hour expiry
                            json.dumps(result)
                        )
                    
            except KeyboardInterrupt:
                logger.info("Worker stopped by user")
                break
            except Exception as e:
                logger.error(f"Worker error: {str(e)}")
                time.sleep(1)


if __name__ == "__main__":
    worker = NetworkWorker()
    worker.run()
