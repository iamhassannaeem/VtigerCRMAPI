<?php
/**
 * Comment Model
 */

class Comment
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get comments for a ticket
     */
    public function getCommentsByTicketId($ticketId, $limit = null)
    {
        $sql = "SELECT 
                    tc.commentid,
                    tc.ticketid,
                    tc.comments,
                    tc.ownerid,
                    tc.ownertype,
                    tc.createdtime,
                    CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                    u.email1 as created_by_email
                FROM vtiger_ticketcomments tc
                LEFT JOIN vtiger_users u ON tc.ownerid = u.id
                WHERE tc.ticketid = ?
                ORDER BY tc.createdtime DESC";

        if ($limit) {
            $sql .= " LIMIT ?";
            return $this->db->fetchAll($sql, [$ticketId, (int)$limit]);
        }

        return $this->db->fetchAll($sql, [$ticketId]);
    }

    /**
     * Get last comment for a ticket
     */
    public function getLastComment($ticketId)
    {
        $comments = $this->getCommentsByTicketId($ticketId, 1);
        return $comments[0] ?? null;
    }

    /**
     * Add comment to ticket
     */
    public function addComment($ticketId, $comment, $userId)
    {
        $sql = "INSERT INTO vtiger_ticketcomments 
                (ticketid, comments, ownerid, ownertype, createdtime)
                VALUES (?, ?, ?, 'user', NOW())";

        $this->db->execute($sql, [$ticketId, $comment, $userId]);

        // Get the created comment
        $commentId = $this->db->lastInsertId();

        // Return all comments for this ticket
        return $this->getCommentsByTicketId($ticketId);
    }

    /**
     * Get recent comments across all tickets
     */
    public function getRecentComments($userId = null, $isAdmin = false, $limit = 5)
    {
        $sql = "SELECT 
                    tc.commentid,
                    tc.ticketid,
                    tc.comments,
                    tc.createdtime,
                    CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                    tt.ticket_no,
                    tt.title as ticket_title
                FROM vtiger_ticketcomments tc
                LEFT JOIN vtiger_users u ON tc.ownerid = u.id
                INNER JOIN vtiger_troubletickets tt ON tc.ticketid = tt.ticketid
                INNER JOIN vtiger_crmentity ce ON tt.ticketid = ce.crmid
                WHERE ce.deleted = 0";

        $params = [];

        // If not admin, restrict to user's assigned tickets
        if (!$isAdmin && $userId) {
            $sql .= " AND ce.smownerid = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY tc.createdtime DESC LIMIT ?";
        $params[] = (int)$limit;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Delete comment
     */
    public function deleteComment($commentId, $userId, $isAdmin = false)
    {
        // Check if user owns the comment or is admin
        $sql = "SELECT ownerid FROM vtiger_ticketcomments WHERE commentid = ?";
        $comment = $this->db->fetchOne($sql, [$commentId]);

        if (!$comment) {
            return false;
        }

        if (!$isAdmin && $comment['ownerid'] != $userId) {
            return false;
        }

        $deleteSql = "DELETE FROM vtiger_ticketcomments WHERE commentid = ?";
        $this->db->execute($deleteSql, [$commentId]);

        return true;
    }
}



