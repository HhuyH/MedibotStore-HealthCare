from pydantic import BaseModel
from typing import List, Dict, Any

class Message(BaseModel):
    message: str
    history: List[Dict[str, Any]] = []
    
class ChatRequest(BaseModel):
    message: str
    history: List[Dict[str, Any]] = []