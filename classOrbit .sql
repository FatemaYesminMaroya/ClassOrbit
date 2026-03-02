CREATE TABLE Student (
  id                   int(11) NOT NULL AUTO_INCREMENT, 
  name                 varchar(255) NOT NULL, 
  email                varchar(255) NOT NULL UNIQUE, 
  password             varchar(255) NOT NULL, 
  pic                  varchar(255), 
  dept                 varchar(255) NOT NULL, 
  prioritypriority_num int(10) NOT NULL, 
  PRIMARY KEY (id));
CREATE TABLE Club (
  id                   int(11) NOT NULL AUTO_INCREMENT, 
  name                 varchar(255) NOT NULL, 
  email                varchar(255) NOT NULL UNIQUE, 
  password             varchar(255) NOT NULL, 
  pic                  varchar(255), 
  clubname             varchar(255) NOT NULL, 
  prioritypriority_num int(10) NOT NULL, 
  PRIMARY KEY (id));
CREATE TABLE Faculty (
  id                   int(11) NOT NULL AUTO_INCREMENT, 
  name                 varchar(255) NOT NULL, 
  email                varchar(255) NOT NULL UNIQUE, 
  password             varchar(255) NOT NULL, 
  pic                  varchar(255), 
  dept                 varchar(255) NOT NULL, 
  prioritypriority_num int(10) NOT NULL, 
  PRIMARY KEY (id));
CREATE TABLE priority (
  priority_num int(10) NOT NULL AUTO_INCREMENT, 
  PRIMARY KEY (priority_num));
CREATE TABLE Admin (
  id       int(10) NOT NULL AUTO_INCREMENT, 
  name     varchar(255) NOT NULL, 
  email    varchar(255) NOT NULL UNIQUE, 
  password varchar(255) NOT NULL, 
  pic      varchar(255), 
  PRIMARY KEY (id));
CREATE TABLE Class_room (
  id            int(11) NOT NULL AUTO_INCREMENT, 
  room_num      int(10) NOT NULL UNIQUE, 
  floor_num     int(10) NOT NULL, 
  capacitie     int(10) NOT NULL, 
  Typetype_name varchar(255) NOT NULL, 
  projector     varchar(255) NOT NULL, 
  AC            varchar(255) NOT NULL, 
  speeker       varchar(255) NOT NULL, 
  any_prob      varchar(255), 
  PRIMARY KEY (id));
CREATE TABLE Type (
  type_name varchar(255) NOT NULL, 
  PRIMARY KEY (type_name));
CREATE TABLE chat_box (
  id        int(10) NOT NULL AUTO_INCREMENT, 
  Mgs       varchar(255), 
  Time      time NOT NULL, 
  Adminid   int(10) NOT NULL, 
  Studentid int(11) NOT NULL, 
  Clubid    int(11) NOT NULL, 
  Facultyid int(11) NOT NULL, 
  PRIMARY KEY (id));
CREATE TABLE Checking (
  id           int(10) NOT NULL AUTO_INCREMENT, 
  time         datetime NOT NULL, 
  available    varchar(255) NOT NULL, 
  Class_roomid int(11) NOT NULL, 
  Adminid      int(10) NOT NULL, 
  PRIMARY KEY (id));
CREATE TABLE `ROOM Booking` (
  id                   int(10) NOT NULL AUTO_INCREMENT, 
  name                 int(10) NOT NULL, 
  dpt                  varchar(255) NOT NULL, 
  email                varchar(255) NOT NULL, 
  reason               varchar(255) NOT NULL, 
  Checkingid           int(10) NOT NULL, 
  prioritypriority_num int(10) NOT NULL, 
  argent_need_thing    varchar(255), 
  PRIMARY KEY (id));
ALTER TABLE Class_room ADD INDEX FKClass_room117110 (Typetype_name), ADD CONSTRAINT FKClass_room117110 FOREIGN KEY (Typetype_name) REFERENCES Type (type_name);
ALTER TABLE Student ADD INDEX FKStudent470793 (prioritypriority_num), ADD CONSTRAINT FKStudent470793 FOREIGN KEY (prioritypriority_num) REFERENCES priority (priority_num);
ALTER TABLE Club ADD INDEX FKClub67165 (prioritypriority_num), ADD CONSTRAINT FKClub67165 FOREIGN KEY (prioritypriority_num) REFERENCES priority (priority_num);
ALTER TABLE Faculty ADD INDEX FKFaculty209857 (prioritypriority_num), ADD CONSTRAINT FKFaculty209857 FOREIGN KEY (prioritypriority_num) REFERENCES priority (priority_num);
ALTER TABLE Checking ADD INDEX FKChecking838251 (Class_roomid), ADD CONSTRAINT FKChecking838251 FOREIGN KEY (Class_roomid) REFERENCES Class_room (id);
ALTER TABLE Checking ADD INDEX FKChecking294187 (Adminid), ADD CONSTRAINT FKChecking294187 FOREIGN KEY (Adminid) REFERENCES Admin (id);
ALTER TABLE `ROOM Booking` ADD INDEX `FKROOM Booki348095` (Checkingid), ADD CONSTRAINT `FKROOM Booki348095` FOREIGN KEY (Checkingid) REFERENCES Checking (id);
ALTER TABLE `ROOM Booking` ADD INDEX `FKROOM Booki564620` (prioritypriority_num), ADD CONSTRAINT `FKROOM Booki564620` FOREIGN KEY (prioritypriority_num) REFERENCES priority (priority_num);
