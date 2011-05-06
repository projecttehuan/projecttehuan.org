<?php
class RiverIdConfig
{
    public static $databaseurl = 'localhost';
    public static $username = 'proje105_usrcic2';
    public static $password = 'proyecto101';
    public static $database = 'proje105_dbSweeper';

    public static $createsql = "CREATE TABLE IF NOT EXISTS users ( username VARCHAR(2000), password VARCHAR(2000), role VARCHAR(2000) ) TYPE=innodb";
}