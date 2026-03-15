-- Reserved character_id 1 = "Nobody" (bye/wildcard placeholder). Used in eliminations and wildcard rounds.
-- Requires a bracket for FK; create a system bracket that owns this character.
USE `anime_bracket`;

INSERT INTO `bracket` (
  `bracket_id`, `bracket_name`, `bracket_perma`, `bracket_start`, `bracket_state`,
  `bracket_rules`, `bracket_source`, `bracket_captcha`
) VALUES (
  1, 'System', 'system', 0, 0, '', 1, 0
);

INSERT INTO `character` (
  `character_id`, `bracket_id`, `character_name`, `character_source`, `character_seed`, `character_meta`
) VALUES (
  1, 1, 'Nobody', '', NULL, NULL
);
