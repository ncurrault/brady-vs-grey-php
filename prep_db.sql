CREATE DATABASE my_db_2;
	
CREATE TABLE Video
(
PID INT NOT NULL AUTO_INCREMENT, 
PRIMARY KEY(PID),

Title CHAR(100), -- The title of the video.  YouTube imposes a 100-character limit.
YouTubeID CHAR(11) -- All IDs should be 11 characters in length.  This can be placed after "http://youtu.be/" to get a short link to a video.
UploadDate DATETIME -- The date/time that the video was uploaded.
Channel CHAR(17) -- The channel name.  Longest is `nottinghamscience`.
Creator CHAR(11) -- Either "Brady Haran" or "C.G.P. Grey" (both are 11 characters)
ViewCount INT -- [0,inf) for an actual count, -1 for a live video, -2 for not calculated (the video is not one that will be listed), -3 for other errors
);

CREATE TABLE UpdateLog
(
PID INT NOT NULL AUTO_INCREMENT, 
PRIMARY KEY(PID),

UpdateDatetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- To update the update time: `Insert Table( DateTimeCol )Values( CURRENT_TIMESTAMP )`