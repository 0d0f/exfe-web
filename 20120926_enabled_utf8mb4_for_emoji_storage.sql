ALTER TABLE   `crosses`           charset=utf8mb4,
MODIFY COLUMN `title`             text CHARACTER SET utf8mb4,
MODIFY COLUMN `description`       text CHARACTER SET utf8mb4,
MODIFY COLUMN `origin_begin_at`   text CHARACTER SET utf8mb4,
MODIFY COLUMN `background`        text CHARACTER SET utf8mb4;

ALTER TABLE   `devices`           charset=utf8mb4,
MODIFY COLUMN `name`              text CHARACTER SET utf8mb4,
MODIFY COLUMN `description`       text CHARACTER SET utf8mb4;

ALTER TABLE   `crosses`           charset=utf8mb4,
MODIFY COLUMN `title`             text CHARACTER SET utf8mb4,
MODIFY COLUMN `description`       text CHARACTER SET utf8mb4,
MODIFY COLUMN `origin_begin_at`   text CHARACTER SET utf8mb4,
MODIFY COLUMN `background`        text CHARACTER SET utf8mb4;

ALTER TABLE   `identities`        charset=utf8mb4,
MODIFY COLUMN `name`              text CHARACTER SET utf8mb4,
MODIFY COLUMN `bio`               text CHARACTER SET utf8mb4,
MODIFY COLUMN `external_username` text CHARACTER SET utf8mb4;

ALTER TABLE   `places`            charset=utf8mb4,
MODIFY COLUMN `place_line1`       text CHARACTER SET utf8mb4,
MODIFY COLUMN `place_line2`       text CHARACTER SET utf8mb4;

ALTER TABLE   `posts`             charset=utf8mb4,
MODIFY COLUMN `title`             text CHARACTER SET utf8mb4,
MODIFY COLUMN `content`           text CHARACTER SET utf8mb4;

ALTER TABLE   `user_relations`    charset=utf8mb4,
MODIFY COLUMN `name`              text CHARACTER SET utf8mb4,
MODIFY COLUMN `external_username` text CHARACTER SET utf8mb4;

ALTER TABLE   `users`             charset=utf8mb4,
MODIFY COLUMN `name`              text CHARACTER SET utf8mb4,
MODIFY COLUMN `bio`               text CHARACTER SET utf8mb4;
