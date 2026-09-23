-- Odkaz na misto konani akce (mapy.cz, google maps apod.)
ALTER TABLE events ADD COLUMN location_url VARCHAR(500) NULL AFTER location;
