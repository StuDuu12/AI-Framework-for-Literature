-- OpenCode Zen free models (zen/v1 endpoint — auto-resolved in helper_ai_settings.php):
--   mimo-v2.5-free, deepseek-v4-flash-free, north-mini-code-free,
--   nemotron-3-ultra-free, big-pickle
UPDATE app_settings SET setting_value = 'mimo-v2.5-free' WHERE setting_key = 'opencode_model';
INSERT INTO app_settings (setting_key, setting_value)
SELECT 'opencode_model', 'mimo-v2.5-free'
WHERE NOT EXISTS (SELECT 1 FROM app_settings WHERE setting_key = 'opencode_model');
