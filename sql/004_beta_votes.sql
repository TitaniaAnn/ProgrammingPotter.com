-- 004_beta_votes.sql — Voting on beta feedback submissions
CREATE TABLE IF NOT EXISTS beta_votes (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    feedback_id INT NOT NULL,
    user_id     INT NOT NULL,
    vote        TINYINT NOT NULL, -- 1 = upvote, -1 = downvote
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_vote (feedback_id, user_id),
    FOREIGN KEY (feedback_id) REFERENCES beta_feedback(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)     REFERENCES beta_users(id)    ON DELETE CASCADE
) ENGINE=InnoDB;
