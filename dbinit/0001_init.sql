-- Snake Game Scores Table
-- Stores score/length/speed for rankings

CREATE TABLE IF NOT EXISTS snake_scores (
    score_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Identity tracking
    member_srl BIGINT UNSIGNED NULL COMMENT 'Rhymix member ID for logged-in users',
    identity_hash CHAR(64) NOT NULL COMMENT 'SHA256 hash for anonymization',
    session_token CHAR(36) NOT NULL COMMENT 'Unique game session token (prevents duplicate submissions)',

    -- Game metrics
    score INT UNSIGNED NOT NULL COMMENT 'Game score',
    length INT UNSIGNED NOT NULL COMMENT 'Final snake length',
    max_speed_fps DECIMAL(5,2) NOT NULL COMMENT 'Fastest speed reached (frames per second)',
    duration_ms INT UNSIGNED NOT NULL COMMENT 'Elapsed time for the run in milliseconds',

    -- Timestamps
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When the score was submitted',

    -- Indexes
    UNIQUE KEY uq_session_token (session_token),
    INDEX idx_ranking (score, length, duration_ms),
    INDEX idx_member_history (member_srl, created_at DESC),
    INDEX idx_identity (identity_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Snake game scores and rankings';
