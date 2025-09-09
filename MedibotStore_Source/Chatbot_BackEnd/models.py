from pydantic import BaseModel
from typing import List, Dict, Any, Optional

class ChatHistoryItem(BaseModel):
    role: str
    content: str

class Message(BaseModel):
    message: str
    user_id: Optional[int] = None
    username: Optional[str] = None
    role: Optional[str] = None
    history: Optional[List[ChatHistoryItem]] = []
    session_id: Optional[str]

class ResetRequest(BaseModel):
    session_id: str
    user_id: Optional[int] = None