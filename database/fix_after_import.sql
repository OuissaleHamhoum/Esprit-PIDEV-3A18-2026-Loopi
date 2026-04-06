-- Exécuter dans phpMyAdmin sur loopi_db APRÈS import du dump, si besoin.
-- 1) Les donations référencent id_collection = 1 alors que certaines bases n'ont que 2 et 3.
UPDATE donation SET id_collection = 2 WHERE id_collection = 1
  AND NOT EXISTS (SELECT 1 FROM collection c WHERE c.id_collection = 1);

-- 2) Si l'ALTER "notifications ADD INDEX idx_type" échoue (index déjà présent dans CREATE), ignorer l'erreur ou :
-- ALTER TABLE notifications DROP INDEX idx_type;
-- ALTER TABLE notifications ADD INDEX idx_type (type);
