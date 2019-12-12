CREATE TABLE tx_edusharing_object (
        uid int(11) unsigned DEFAULT '0' NOT NULL auto_increment,
        objecturl varchar(255) DEFAULT '' NOT NULL,
        contentid int(11) unsigned DEFAULT '0' NOT NULL,
        title varchar(255) DEFAULT '' NOT NULL,
        version varchar(255) DEFAULT '-1' NOT NULL,
        mimetype varchar(255) DEFAULT '' NOT NULL,
        PRIMARY KEY (uid),
);
