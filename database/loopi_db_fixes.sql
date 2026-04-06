-- loopi_db: corrections after importing the phpMyAdmin dump
--
-- Import issues to fix in the dump file itself (before import):
-- A) notifications: CREATE TABLE already defines FOREIGN KEY for id_user and id_evenement.
--    Remove the duplicate block:
--      ALTER TABLE `notifications` ADD CONSTRAINT `notifications_ibfk_1` ...
--      ALTER TABLE `notifications` ADD CONSTRAINT `notifications_ibfk_2` ...
-- B) notifications: idx_type is already in CREATE TABLE; remove:
--      ALTER TABLE notifications ADD INDEX idx_type (type);
-- C) Donations reference id_collection = 1 but the dump only inserts collections 2 and 3.
--    Run the INSERT below.

INSERT INTO collection (id_collection, title, material_type, image_collection, goal_amount, current_amount, unit, status, id_user, created_at, updated_at)
VALUES (1, 'Collection initiale', 'Mixte', '', 100, 8.2, 'kg', 'active', 2, NOW(), NOW())
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- Sample notifications: after inserting the 5 new evenement rows, ids are 3–7 (after existing 1–2).
-- If your notification messages do not match events, replace them with:
--
-- TRUNCATE notifications;
-- INSERT INTO notifications (id_user, type, titre, message, is_read, id_evenement, nom_organisateur, email_organisateur, event_titre) VALUES
-- (3, 'inscription', 'Inscription confirmée', 'Vous êtes inscrit à l''événement Collecte de plastique', 0, 3, 'Organisateur Eco', 'organisateur@loopi.tn', 'Collecte de plastique'),
-- (4, 'inscription', 'Participation enregistrée', 'Votre participation à Atelier recyclage créatif est confirmée', 0, 5, 'Organisateur Eco', 'organisateur@loopi.tn', 'Atelier recyclage créatif'),
-- (2, 'nouvelle_participation', 'Nouveau participant', 'Un participant vient de rejoindre votre événement', 0, 6, 'Pierre Martin', 'pierre@email.com', 'Collecte de verre'),
-- (3, 'rappel', 'Rappel événement', 'Rappel : votre événement commence bientôt', 0, 6, 'Pierre Martin', 'pierre@email.com', 'Collecte de verre'),
-- (7, 'nouvel_evenement', 'Nouvel événement disponible', 'Un nouvel événement écologique est disponible', 0, 7, 'Organisateur Eco', 'organisateur@loopi.tn', 'Formation tri des déchets');
