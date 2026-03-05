<?php
/**
 * Comment Model
 * Uses standard Vtiger comment structure: vtiger_modcomments + vtiger_crmentity
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
     * Uses standard Vtiger structure: vtiger_modcomments + vtiger_crmentity
     * All comments created via this API are stored as private/internal (is_private = 1).
     */
    public function getCommentsByTicketId($ticketId, $limit = null)
    {
        $sql = "SELECT 
                    mc.modcommentsid as commentid,
                    mc.related_to as ticketid,
                    mc.commentcontent as comments,
                    mc.userid as ownerid,
                    'Users' as ownertype,
                    ce.createdtime,
                    ce.modifiedtime,
                    ce.smownerid,
                    CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                    u.email1 as created_by_email,
                    mc.parent_comments,
                    mc.is_private
                FROM vtiger_modcomments mc
                INNER JOIN vtiger_crmentity ce ON mc.modcommentsid = ce.crmid AND ce.setype = 'ModComments'
                LEFT JOIN vtiger_users u ON mc.userid = u.id
                WHERE mc.related_to = ? AND ce.deleted = 0
                ORDER BY ce.createdtime DESC";

        $params = [$ticketId];
        
        if ($limit) {
            $sql .= " LIMIT ?";
            $params[] = (int)$limit;
        }

        return $this->db->fetchAll($sql, $params);
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
     * Uses standard Vtiger structure: inserts into vtiger_crmentity then vtiger_modcomments
     * All comments are stored as private/internal by default (is_private = 1).
     */
    public function addComment($ticketId, $comment, $userId, $parentCommentId = 0, $isPrivate = 1)
    {
        // Get current time in Pakistan timezone (Asia/Karachi)
        $pakistanTime = new DateTime('now', new DateTimeZone('Asia/Karachi'));
        $createdTime = $pakistanTime->format('Y-m-d H:i:s');
        
        try {
            $this->db->beginTransaction();

            // 1. Get next entity ID (crmid = modcommentsid)
            $modCommentId = $this->getNextEntityId();

            // 2. Insert into vtiger_crmentity (required by Vtiger)
            $label = substr($comment, 0, 100); // Label is first 100 chars
            $sqlEntity = "INSERT INTO vtiger_crmentity 
                         (crmid, smcreatorid, smownerid, modifiedby, setype, description, 
                          createdtime, modifiedtime, deleted, source, label)
                         VALUES 
                         (?, ?, ?, ?, 'ModComments', '', ?, ?, 0, 'CRM', ?)";
            $this->db->execute($sqlEntity, [
                $modCommentId,
                $userId,
                $userId,
                $userId,
                $createdTime,
                $createdTime,
                $label
            ]);

            // 3. Insert into vtiger_modcomments (standard Vtiger comment table)
            $sqlModComments = "INSERT INTO vtiger_modcomments 
                              (modcommentsid, commentcontent, related_to, parent_comments, userid, is_private)
                              VALUES (?, ?, ?, ?, ?, ?)";
            $this->db->execute($sqlModComments, [
                $modCommentId,
                $comment,
                $ticketId,
                $parentCommentId, // 0 for top-level, comment ID for replies
                $userId,
                $isPrivate
            ]);

            $this->db->commit();

            // Return all comments for this ticket
            return $this->getCommentsByTicketId($ticketId);

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Get next entity ID from vtiger_crmentity_seq
     * Same logic as Ticket model
     */
    private function getNextEntityId()
    {
        // Get the maximum ID from vtiger_crmentity to ensure sequence is correct
        $sqlMax = "SELECT MAX(crmid) as max_id FROM vtiger_crmentity";
        $maxResult = $this->db->fetchOne($sqlMax);
        $maxExistingId = $maxResult['max_id'] ?? 0;
        
        // Get current ID from sequence table
        $sql = "SELECT id FROM vtiger_crmentity_seq FOR UPDATE";
        $result = $this->db->fetchOne($sql);
        
        if (!$result) {
            throw new Exception("Unable to get next entity ID from sequence table");
        }
        
        $sequenceId = $result['id'];
        
        // Ensure sequence is ahead of existing data
        $nextId = max($sequenceId, $maxExistingId + 1);
        
        // Update sequence table with next ID
        $sqlUpdate = "UPDATE vtiger_crmentity_seq SET id = ?";
        $this->db->execute($sqlUpdate, [$nextId + 1]);
        
        return $nextId;
    }

    /**
     * Get recent comments across all tickets
     */
    public function getRecentComments($userId = null, $isAdmin = false, $limit = 5)
    {
        $sql = "SELECT 
                    mc.modcommentsid as commentid,
                    mc.related_to as ticketid,
                    mc.commentcontent as comments,
                    ce.createdtime,
                    CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                    tt.ticket_no,
                    tt.title as ticket_title
                FROM vtiger_modcomments mc
                INNER JOIN vtiger_crmentity ce ON mc.modcommentsid = ce.crmid AND ce.setype = 'ModComments'
                LEFT JOIN vtiger_users u ON mc.userid = u.id
                INNER JOIN vtiger_troubletickets tt ON mc.related_to = tt.ticketid
                INNER JOIN vtiger_crmentity ce_ticket ON tt.ticketid = ce_ticket.crmid
                WHERE ce.deleted = 0 AND ce_ticket.deleted = 0";

        $params = [];

        // If not admin, restrict to user's assigned tickets
        if (!$isAdmin && $userId) {
            $sql .= " AND ce_ticket.smownerid = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY ce.createdtime DESC LIMIT ?";
        $params[] = (int)$limit;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get child comments (replies)
     */
    public function getChildComments($parentCommentId, $ticketId)
    {
        $sql = "SELECT 
                    mc.modcommentsid as commentid,
                    mc.related_to as ticketid,
                    mc.commentcontent as comments,
                    mc.userid as ownerid,
                    'Users' as ownertype,
                    ce.createdtime,
                    CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
                    u.email1 as created_by_email,
                    mc.parent_comments
                FROM vtiger_modcomments mc
                INNER JOIN vtiger_crmentity ce ON mc.modcommentsid = ce.crmid AND ce.setype = 'ModComments'
                LEFT JOIN vtiger_users u ON mc.userid = u.id
                WHERE mc.parent_comments = ? AND mc.related_to = ? AND ce.deleted = 0
                ORDER BY ce.createdtime ASC";

        return $this->db->fetchAll($sql, [$parentCommentId, $ticketId]);
    }

    /**
     * Delete comment
     * Uses soft delete (sets deleted = 1 in vtiger_crmentity) - Vtiger standard
     */
    public function deleteComment($commentId, $userId, $isAdmin = false)
    {
        // Check if comment exists and get owner info
        $sql = "SELECT mc.userid, ce.deleted 
                FROM vtiger_modcomments mc
                INNER JOIN vtiger_crmentity ce ON mc.modcommentsid = ce.crmid AND ce.setype = 'ModComments'
                WHERE mc.modcommentsid = ?";
        $comment = $this->db->fetchOne($sql, [$commentId]);

        if (!$comment || $comment['deleted'] == 1) {
            return false;
        }

        if (!$isAdmin && $comment['userid'] != $userId) {
            return false;
        }

        try {
            $this->db->beginTransaction();

            // Soft delete: mark as deleted in vtiger_crmentity (Vtiger standard)
            $deleteSql = "UPDATE vtiger_crmentity SET deleted = 1 WHERE crmid = ? AND setype = 'ModComments'";
            $this->db->execute($deleteSql, [$commentId]);

            $this->db->commit();
            return true;

        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
