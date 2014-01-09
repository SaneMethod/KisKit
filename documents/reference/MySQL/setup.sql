-- This script can be run to configure MySQL from the command line.
-- You probably want to run it like this::
-- 
--     $ mysql -u root < path/to/setup.sql
--
-- When it completes, you can login to MySQL as the ``cedb`` user like this::
--
--     $ mysql -u cedb -p
--
CREATE DATABASE cedb;
GRANT USAGE ON cedb.* TO 'cedb'@'localhost' IDENTIFIED BY 'ceck93z%!NWzy3R^@!5w8>h';
GRANT SELECT, INSERT, UPDATE, CREATE, CREATE TEMPORARY TABLES, SHOW VIEW, EXECUTE ON cedb.* TO 'cedb'@'localhost';
GRANT SHOW DATABASES on *.* TO 'cedb'@'localhost';
