<?php
/**
 * Storage bootstrap for community votes on ideas.
 */
function ensureProjectVotesTable(PDO $db): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $db->exec("
        CREATE TABLE IF NOT EXISTS project_votes (
            vote_id SERIAL PRIMARY KEY,
            project_id INTEGER NOT NULL,
            voter_id INTEGER NOT NULL,
            voted_at TIMESTAMP NOT NULL DEFAULT NOW(),
            UNIQUE (project_id, voter_id)
        )
    ");

    $db->exec("CREATE INDEX IF NOT EXISTS idx_project_votes_project ON project_votes(project_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_project_votes_voter ON project_votes(voter_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_project_votes_project_voted_at ON project_votes(project_id, voted_at DESC)");

    $db->exec("
        CREATE TABLE IF NOT EXISTS project_vote_events (
            event_id SERIAL PRIMARY KEY,
            project_id INTEGER NOT NULL,
            voter_id INTEGER NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT (CURRENT_TIMESTAMP AT TIME ZONE 'Africa/Luanda')
        )
    ");

    $db->exec("CREATE INDEX IF NOT EXISTS idx_project_vote_events_voter_created ON project_vote_events(voter_id, created_at DESC)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_project_vote_events_project ON project_vote_events(project_id)");

    $checked = true;
}
