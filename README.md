:warning: This extension may need further improvement depending on your needs. Send [me](mailto:mail2andyk@gmail.com) a message if something goes wrong.

## Purpose
Simple advanced features for comments on the product page or any informational or blog page of an OpenCart 3.x online store. These features include likes for comments and multilevel comments (up to 3d level in this aproach, customer's reply on a particular third-level-comment will display on the same 3d level after that comment).

## When it's important?
Very often for commercial products. You can see votes, likes or dislikes, share links almost on every modern commercial website that includes customers comments somewhere. Even these comments may be organised in multilevel structure.

I added some screenshots to demonstrate how it works.
Displaying comment with no votes for not logged user on product page:<br/>
<img src="https://github.com/AndrewKreshchenko/Comments-and-votes-OpenCart-3/blob/master/docs/comment-product-not-logged.png"><br/>
Displaying comment with no votes for not not logged user on product page:<br/>
<img src="https://github.com/AndrewKreshchenko/Comments-and-votes-OpenCart-3/blob/master/docs/comment-product-logged.png"><br/>
<br/>
Displaying comments on the top and third levels for logged user on TLT Blog page:<br/>
![Displaying comment on the top level for logged user on TLT Blog page](https://github.com/AndrewKreshchenko/Comments-and-votes-OpenCart-3/blob/master/docs/comment-product-not-logged.png)
![Displaying comment on the top level for logged user on TLT Blog page](https://github.com/AndrewKreshchenko/Comments-and-votes-OpenCart-3/blob/master/docs/top-hierarchy-comment-blog-page-logged.jpg)
![Displaying comment on the third level for logged user on TLT Blog page](https://github.com/AndrewKreshchenko/Comments-and-votes-OpenCart-3/blob/master/docs/3d-hierarchy-comment-blog-page-logged.jpg.jpg)

## Prerequisites before usage
* Upload files according to the standard OpenCart MVC structure. Then review each file of my extension and insert code between commented 3 dots ("...") into corresponding file. Please, be careful to paste parts of code to the right places.
* You have to create table(s) in MySQL database (further description).
* If You going to insert parts of code for TLT Blog, please, visit the [official page](https://www.opencart.com/index.php?route=marketplace/extension/info&extension_id=24602) of this free module. (I used TLT Blog for Opencart 3.0.x. license_tltblog.txt is in the root directory). catalog/controller/extension/tltblog/tltblog.php contains realized multilevel comments building approach.
* Test your result. CSS styles on your page may not dislay correctly, especially when your theme is not "default".

### Prepare MySQL DB
Firstly I'd like to demonstrate how to prepare table for product page. Go to administration tool of MySQL (I guess you use `phpMyAdmin`), choose the right database and click on tab SQL Command to run SQL commands.
1) OpenCart database should have tables called `oc_review`. There you need to add 2 columns to store. Replace \`your_database\` with the name of your database.

```
ALTER TABLE `your_database`.`oc_review` ADD COLUMN `approval` TINYINT(1) NOT NULL
ALTER TABLE `your_database`.`oc_review` ADD COLUMN `disapproval` TINYINT(1) NOT NULL
```
2) Create new table `oc_review_approval` (it will have many-to-many relationship with `oc_review`):
```
CREATE TABLE `your_database`.`oc_review_approval` (
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
```

Also, I show an example of preparing tables for reviews on TLT Blog pages. By default the free version of this blog extension doesn't provide reviews functionality. So, if you need this, you have to create 2 tables.

```
CREATE TABLE `your_database`.`oc_blog_review` (
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

CREATE TABLE `your_database`.`oc_blog_review_approval` (
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
```

### Tests
For product page:
```
SELECT * FROM `your_database`.`oc_review` WHERE review_id = "1" // 1, for instance
```
Count number of columns by specific criteria: 
```
SELECT COUNT(*) FROM `your_database`.COLUMNS WHERE TABLE_NAME='oc_review' AND column_name='review_id'
```

You can manually insert a review into `oc_blog_review`:
```
INSERT INTO `oc_blog_review` SET author = 'Andy Kre', customer_id = '1', tltblog_id = '2', text = 'Yeah)', depth = '2', date_added = NOW();
```

To get a review from `oc_blog_review` run something like this:
```
SELECT r.review_id, r.author, r.depth, r.text, r.approval, r.disapproval, b.tltblog_id, r.date_added FROM `oc_blog_review` r LEFT JOIN `oc_tltblog` b ON (r.tltblog_id = b.tltblog_id) LEFT JOIN `oc_tltblog_description` bd ON (b.tltblog_id = bd.tltblog_id) WHERE b.tltblog_id = '2' AND b.status = '1' AND r.status = '0' AND bd.language_id = '2' ORDER BY r.date_added DESC;
```
## Contribution
:open_hands: I'll feel happy if you will decide to fetch this project for development. :smiley:
