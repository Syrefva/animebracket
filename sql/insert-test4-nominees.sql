-- Insert 32 nominees into bracket "test4" and create 32 characters (entrants) as if processed manually.
-- Run this after the test4 bracket exists. Uses bracket_id from bracket_perma = 'test4'.
-- After running this SQL, run: php cli/save-test4-character-images.php

USE `anime_bracket`;

SET @bracket_id = (SELECT bracket_id FROM bracket WHERE bracket_perma = 'test4' LIMIT 1);
SET @image_url = 'https://www.opportunityhome.org/wp-content/uploads/2013/03/image-alignment-150x150.jpg';
SET @created = UNIX_TIMESTAMP();

-- 1) Nominees (marked processed so they won't show in Process Nominees)
INSERT INTO nominee (bracket_id, nominee_name, nominee_source, nominee_created, nominee_processed, nominee_image) VALUES
(@bracket_id, 'Nominee 1',  NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 2',  NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 3',  NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 4',  NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 5',  NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 6',  NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 7',  NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 8',  NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 9',  NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 10', NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 11', NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 12', NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 13', NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 14', NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 15', NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 16', NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 17', NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 18', NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 19', NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 20', NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 21', NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 22', NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 23', NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 24', NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 25', NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 26', NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 27', NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 28', NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 29', NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 30', NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 31', NULL, @created, 1, @image_url),
(@bracket_id, 'Nominee 32', NULL, @created, 1, @image_url);

-- 2) Characters (entrants) — same names; app serves images from IMAGE_LOCATION/{id_base36}.jpg
INSERT INTO `character` (bracket_id, character_name, character_source, character_seed, character_meta) VALUES
(@bracket_id, 'Nominee 1',  '', NULL, NULL),
(@bracket_id, 'Nominee 2',  '', NULL, NULL),
(@bracket_id, 'Nominee 3',  '', NULL, NULL),
(@bracket_id, 'Nominee 4',  '', NULL, NULL),
(@bracket_id, 'Nominee 5',  '', NULL, NULL),
(@bracket_id, 'Nominee 6',  '', NULL, NULL),
(@bracket_id, 'Nominee 7',  '', NULL, NULL),
(@bracket_id, 'Nominee 8',  '', NULL, NULL),
(@bracket_id, 'Nominee 9',  '', NULL, NULL),
(@bracket_id, 'Nominee 10', '', NULL, NULL),
(@bracket_id, 'Nominee 11', '', NULL, NULL),
(@bracket_id, 'Nominee 12', '', NULL, NULL),
(@bracket_id, 'Nominee 13', '', NULL, NULL),
(@bracket_id, 'Nominee 14', '', NULL, NULL),
(@bracket_id, 'Nominee 15', '', NULL, NULL),
(@bracket_id, 'Nominee 16', '', NULL, NULL),
(@bracket_id, 'Nominee 17', '', NULL, NULL),
(@bracket_id, 'Nominee 18', '', NULL, NULL),
(@bracket_id, 'Nominee 19', '', NULL, NULL),
(@bracket_id, 'Nominee 20', '', NULL, NULL),
(@bracket_id, 'Nominee 21', '', NULL, NULL),
(@bracket_id, 'Nominee 22', '', NULL, NULL),
(@bracket_id, 'Nominee 23', '', NULL, NULL),
(@bracket_id, 'Nominee 24', '', NULL, NULL),
(@bracket_id, 'Nominee 25', '', NULL, NULL),
(@bracket_id, 'Nominee 26', '', NULL, NULL),
(@bracket_id, 'Nominee 27', '', NULL, NULL),
(@bracket_id, 'Nominee 28', '', NULL, NULL),
(@bracket_id, 'Nominee 29', '', NULL, NULL),
(@bracket_id, 'Nominee 30', '', NULL, NULL),
(@bracket_id, 'Nominee 31', '', NULL, NULL),
(@bracket_id, 'Nominee 32', '', NULL, NULL);
