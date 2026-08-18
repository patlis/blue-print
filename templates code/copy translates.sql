INSERT INTO `XXXX_patlis_com`.`wp_f4bb67_patlis_translations`
    (`translation_key`, `lang`, `translation_value`, `created_at`)
SELECT
    s.`translation_key`,
    s.`lang`,
    s.`translation_value`,
    s.`created_at`
FROM `dev_patli_arnn8a`.`wp_f4bb67_patlis_translations` AS s
WHERE NOT EXISTS (
    SELECT 1
    FROM `XXXX_patlis_com`.`wp_f4bb67_patlis_translations` AS t
    WHERE t.`translation_key` = s.`translation_key`
      AND t.`lang` = s.`lang`
);