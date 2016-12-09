CREATE TABLE craigScan_list (
`id` BIGINT NOT NULL PRIMARY KEY,
search_term varchar(100) not null,
email_address varchar(100) not null,
title varchar(2000) not null,
img_url varchar(200) null,
description varchar(2000) not null,
category varchar(25) null,
url varchar(200) null,
last_update TIMESTAMP not null default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP
)ENGINE=innodb;
