DROP TABLE IF EXISTS wp_smartPDF;

CREATE TABLE wp_smartPDF
(
   FileID varchar(50) NOT NULL,
   Email varchar(500) NOT NULL,
   FormName varchar(100),
   FormStatus enum('ACTIVE', 'DELETED') default 'ACTIVE',
   FileName varchar(100) NOT NULL,
   UserID bigint(20) unsigned default 1,
   DateEntered datetime default '1000-01-01 00:00:00',
   DownloadURL varchar(250),
   CustomField1 varchar(50),
   CustomField2 varchar(50),
   CustomField3 varchar(50),
   CustomField4 varchar(50),
   CustomField5 varchar(50),
   CustomField6 varchar(50),
   CustomField7 varchar(50),
   CustomField8 varchar(50),
   CustomField9 varchar(50),
   CustomField10 varchar(50),
   PRIMARY KEY (FileID),
   INDEX (FileID)
);


DROP TABLE IF EXISTS wp_smartPDF_data;

CREATE TABLE wp_smartPDF_data
(
	TransactionID varchar(20) NOT NULL,
	FileID varchar(50) NOT NULL,
	SubmittedData TEXT,
	FileAttachmentPath varchar(250),
	FileAttachmentCount	smallint(5),
	SourceIP varchar(50),
	TimeStamp datetime default '1000-01-01 00:00:00',
	Status enum('NEW', 'OPEN', 'CLOSED') default 'NEW',
	Notes TEXT,
	AccessedBy varchar(50),
	LastAccessed datetime,
	LastModified datetime,
	ModifiedBy varchar(50),
	CustomFieldValue1 varchar(250),
    CustomFieldValue2 varchar(250),
    CustomFieldValue3 varchar(250),
    CustomFieldValue4 varchar(250),
    CustomFieldValue5 varchar(250),
    CustomFieldValue6 varchar(250),
    CustomFieldValue7 varchar(250),
    CustomFieldValue8 varchar(250),
    CustomFieldValue9 varchar(250),
    CustomFieldValue10 varchar(250),
	PRIMARY KEY (TransactionID),
	INDEX(TransactionID, FileID)
);
