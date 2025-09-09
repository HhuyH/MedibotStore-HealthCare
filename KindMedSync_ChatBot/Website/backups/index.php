<?php
// Prevent direct access to backup directory
http_response_code(403);
exit('Access denied');
?> 