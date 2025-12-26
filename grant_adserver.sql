CREATE USER IF NOT EXISTS 'adserver'@'localhost' IDENTIFIED WITH mysql_native_password BY 'mkuKwrUqK2aFiW8VvZlwQNNarIRYSf';
GRANT ALL PRIVILEGES ON adserver.* TO 'adserver'@'localhost';
FLUSH PRIVILEGES;
