<?php
// includes/XPHandler.php
require_once __DIR__ . '/../configuracoes/base_dados.php';

class XPHandler {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function addXP($user_id, $amount, $impact = 0) {
        $stmt = $this->db->prepare("UPDATE users SET xp_points = xp_points + :xp, impact_score = impact_score + :impact WHERE user_id = :uid");
        $stmt->execute(['xp' => $amount, 'impact' => $impact, 'uid' => $user_id]);

        // Level Up Logic: Level increases every 1000 XP (simplified)
        $user_stmt = $this->db->prepare("SELECT xp_points FROM users WHERE user_id = ?");
        $user_stmt->execute([$user_id]);
        $xp = $user_stmt->fetchColumn();
        
        $new_level = floor($xp / 1000) + 1;
        
        $update_level = $this->db->prepare("UPDATE users SET user_level = ? WHERE user_id = ?");
        $update_level->execute([$new_level, $user_id]);
        
        return $new_level;
    }
}
