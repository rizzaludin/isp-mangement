from pydantic_settings import BaseSettings
from typing import Optional


class Settings(BaseSettings):
    # Database
    db_host: str = "localhost"
    db_port: int = 5432
    db_name: str = "isp_management"
    db_user: str = "isp_user"
    db_password: str = "isp_password"
    
    # Redis
    redis_host: str = "localhost"
    redis_port: int = 6379
    redis_db: int = 0
    
    # Backend API
    backend_api_url: str = "http://localhost:8000"
    backend_api_key: Optional[str] = None
    
    # Worker
    worker_concurrency: int = 4
    log_level: str = "INFO"
    
    class Config:
        env_file = ".env"
        case_sensitive = False


settings = Settings()
