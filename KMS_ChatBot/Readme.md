*updatingg......

Note
down redis libary và https://github.com/tporadowski/redis/releases
tạo file 

tạo 1 file redis.conf với nội dung

save 60 1
appendonly yes
appendfsync everysec
dir ./
dbfilename dump.rdb
appendfilename "appendonly.aof"

chạy trên cmd
redis-server.exe redis.conf

chạy FastAPI
uvicorn main:app --reload