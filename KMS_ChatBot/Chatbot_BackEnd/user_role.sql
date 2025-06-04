CREATE USER 'chatbot_user'@'%' IDENTIFIED BY '123';

GRANT SELECT, INSERT, UPDATE, DELETE ON kms.* TO 'chatbot_user'@'%';
