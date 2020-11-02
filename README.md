#This 


TLT Blog for Opencart 3.0.x
https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=24602
```



1) Extend table that is exists:
ALTER TABLE forma02_oc3dev.`oc_review` ADD COLUMN `approval` TINYINT(1) NOT NULL
ALTER TABLE forma02_oc3dev.`oc_review` ADD COLUMN `disapproval` TINYINT(1) NOT NULL

2) Create new table:
CREATE TABLE forma02_oc3dev.`oc_review_approval` (
    customer_id int NOT NULL,
    review_id int NOT NULL,
    FOREIGN KEY (customer_id) REFERENCES oc_review(customer_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    approval BOOLEAN NOT NULL,
    FOREIGN KEY (review_id) REFERENCES oc_review(review_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    PRIMARY KEY (customer_id, review_id)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;


Reviews
https://itc.ua/news/kievstar-vklyuchil-4g-svyaz-na-chastotah-900-mgcz-v-75-naselyonnyh-punktah-kievskoj-oblasti/
CREATE TABLE `oc_blog_review` (
  `review_id` int(11) NOT NULL AUTO_INCREMENT,
  `tltblog_id` int(11) NOT NULL,
  `customer_id` int(11) NOT NULL,
  `depth` tinyint(1) NOT NULL,
  `related` tinyint(1) NOT NULL DEFAULT '0',
  `author` varchar(64) NOT NULL,
  `text` text NOT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `date_added` datetime NOT NULL,
  `date_modified` datetime,
  `approval` tinyint(1) NOT NULL,
  `disapproval` tinyint(1) NOT NULL,
  PRIMARY KEY (`review_id`),
  KEY `tltblog_id` (`tltblog_id`)
) ENGINE=MyISAM AUTO_INCREMENT=4 DEFAULT CHARSET=utf8;

CREATE TABLE forma02_oc3dev.`oc_blog_review_approval` (
    `customer_id` int NOT NULL,
    `review_id` int NOT NULL,
    FOREIGN KEY (`customer_id`) REFERENCES `oc_blog_review`(`customer_id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    `approval` BOOLEAN NOT NULL,
    FOREIGN KEY (`review_id`) REFERENCES `oc_blog_review`(`review_id`)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    PRIMARY KEY (`customer_id`, `review_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

1) Чи може один і той самий автор коментувати двічі і більше? так
2) Чи буде автор їх модифікувати? Так
3) Чи відображати по статусам 1 відгуки чи без цього?
AND b.status = '1' AND r.status = '0' AND bd.language_id

Tests
product review
SELECT * FROM `oc_review` WHERE review_id = "1" // in adminer
SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='oc_review' AND column_name='review_id'

Insert review:
INSERT INTO `oc_blog_review` SET author = 'Andy Kre', customer_id = '1', tltblog_id = '2', text = 'Yeah)', depth = '2', date_added = NOW();

Get Review:
SELECT r.review_id, r.author, r.depth, r.text, r.approval, r.disapproval, b.tltblog_id, r.date_added FROM `oc_blog_review` r LEFT JOIN `oc_tltblog` b ON (r.tltblog_id = b.tltblog_id) LEFT JOIN `oc_tltblog_description` bd ON (b.tltblog_id = bd.tltblog_id) WHERE b.tltblog_id = '2' AND b.status = '1' AND r.status = '0' AND bd.language_id = '2' ORDER BY r.date_added DESC;


```