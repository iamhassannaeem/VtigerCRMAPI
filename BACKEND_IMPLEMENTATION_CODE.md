# Backend Implementation Code - Complete API Response Structures

This document contains **complete backend implementation code** (PHP/Laravel) that matches the frontend's expected response structures for all API endpoints.

---

## Table of Contents

1. [Authentication API](#1-authentication-api)
2. [Dashboard API](#2-dashboard-api)
3. [Tickets API](#3-tickets-api)
4. [Profile API](#4-profile-api)
5. [Comments API](#5-comments-api)
6. [Notifications API](#6-notifications-api)
7. [Ticket Detail API](#7-ticket-detail-api)
8. [Common Response Helpers](#8-common-response-helpers)

---

## 1. Authentication API

### Endpoint: `POST /auth/login`

**Request Body:**
```json
{
    "username": "admin",
    "password": "password123"
}
```

**Backend Implementation (Laravel/PHP):**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    /**
     * Login user and return token with user info
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('username', 'password');
        
        // Find user by username
        $user = User::where('username', $credentials['username'])->first();
        
        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid username or password.'
            ], 401);
        }

        // Generate JWT token
        $token = JWTAuth::fromUser($user);
        
        // Get role type based on user's role
        $roleType = $this->getRoleType($user->role);
        
        // Build user response with role_info
        $userResponse = [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'is_admin' => (bool) $user->is_admin,
            'role' => $user->role,
            'department' => $user->department ?? '',
            'role_info' => [
                'type' => $roleType, // REQUIRED: "NOC", "Field", "Management", "Sales", or "Normal"
                'full_name' => trim($user->first_name . ' ' . $user->last_name),
                'base_name' => $this->getBaseRoleName($user->role),
            ],
        ];

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => $userResponse,
        ], 200);
    }

    /**
     * Map user role to role_info.type
     * This is CRITICAL for dashboard routing in the mobile app
     */
    private function getRoleType($role)
    {
        $roleLower = strtolower($role);
        
        // Check for NOC
        if (strpos($roleLower, 'noc') !== false) {
            return 'NOC';
        }
        
        // Check for Field
        if (strpos($roleLower, 'field') !== false) {
            return 'Field';
        }
        
        // Check for Management/CEO
        if (strpos($roleLower, 'management') !== false || 
            strpos($roleLower, 'ceo') !== false ||
            strpos($roleLower, 'manager') !== false) {
            return 'Management';
        }
        
        // Check for Sales
        if (strpos($roleLower, 'sales') !== false) {
            return 'Sales';
        }
        
        // Default to Normal
        return 'Normal';
    }

    /**
     * Get base role name for role_info
     */
    private function getBaseRoleName($role)
    {
        $roleType = $this->getRoleType($role);
        return $roleType;
    }

    /**
     * Verify authentication token
     * Endpoint: GET /auth/verify
     */
    public function verify(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'valid' => false,
                    'message' => 'User not found'
                ], 401);
            }

            return response()->json([
                'valid' => true,
                'user' => [
                    'id' => (string) $user->id,
                    'username' => $user->username,
                    'email' => $user->email ?? null,
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Token is invalid'
            ], 401);
        }
    }
}
```

**Expected Response:**
```json
{
    "success": true,
    "message": "Login successful",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "user": {
        "id": 1,
        "username": "admin",
        "email": "noc@conectel.com.pk",
        "first_name": "ConnecTEL",
        "last_name": "Private Limited",
        "is_admin": true,
        "role": "CEO",
        "department": "",
        "role_info": {
            "type": "Management",
            "full_name": "ConnecTEL Private Limited",
            "base_name": "Management"
        }
    }
}
```

---

## 2. Dashboard API

### Endpoint: `GET /mobile/dashboard`

**Query Parameters (optional):**
- `start_date` (string, format: YYYY-MM-DD)
- `end_date` (string, format: YYYY-MM-DD)

**Backend Implementation (Laravel/PHP):**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Get dashboard data based on user role
     */
    public function getDashboard(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication failed. Please login again.'
                ], 401);
            }

            // Get date range from query params or use defaults
            $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
            $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));

            // Get role type from user's role_info (should be stored in user model or calculated)
            $roleType = $this->getRoleType($user->role);
            $roleTypeLower = strtolower($roleType);

            // Route to appropriate dashboard data method
            switch ($roleTypeLower) {
                case 'noc':
                    $data = $this->getNocDashboardData($user, $startDate, $endDate);
                    break;
                case 'field':
                    $data = $this->getFieldDashboardData($user, $startDate, $endDate);
                    break;
                case 'management':
                    $data = $this->getManagementDashboardData($user, $startDate, $endDate);
                    break;
                case 'sales':
                    $data = $this->getSalesDashboardData($user, $startDate, $endDate);
                    break;
                default:
                    $data = $this->getNormalDashboardData($user, $startDate, $endDate);
                    $roleTypeLower = 'normal';
            }

            return response()->json([
                'success' => true,
                'role_type' => $roleTypeLower, // lowercase: "normal", "noc", "management", "sales", "field"
                'data' => $data,
                'date_range' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'message' => 'Dashboard data retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Management Dashboard Data
     */
    private function getManagementDashboardData($user, $startDate, $endDate)
    {
        // KPIs
        $kpis = [
            'open_tickets' => (int) Ticket::where('status', 'Open')
                ->whereBetween('createdtime', [$startDate, $endDate])
                ->count(),
            'in_progress_tickets' => (int) Ticket::where('status', 'In Progress')
                ->whereBetween('createdtime', [$startDate, $endDate])
                ->count(),
            'wait_for_response_tickets' => (int) Ticket::where('status', 'Wait For Response')
                ->whereBetween('createdtime', [$startDate, $endDate])
                ->count(),
            'closed_tickets' => (int) Ticket::where('status', 'Closed')
                ->whereBetween('createdtime', [$startDate, $endDate])
                ->count(),
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ];

        // Trends - Tickets created vs resolved
        $ticketTrends = DB::table('tickets')
            ->select(
                DB::raw('DATE(createdtime) as date'),
                DB::raw('COUNT(CASE WHEN status = "Closed" THEN 1 END) as resolved'),
                DB::raw('COUNT(*) as created')
            )
            ->whereBetween('createdtime', [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(createdtime)'))
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'resolved' => (int) $item->resolved,
                    'created' => (int) $item->created,
                ];
            });

        // Priority trends
        $priorityTrends = DB::table('tickets')
            ->select('priority', DB::raw('COUNT(*) as count'))
            ->whereBetween('createdtime', [$startDate, $endDate])
            ->groupBy('priority')
            ->get()
            ->map(function ($item) {
                return [
                    'priority' => $item->priority,
                    'count' => (int) $item->count,
                    'trend' => 'up', // Calculate based on previous period
                ];
            });

        // Team Performance
        $teamPerformance = DB::table('tickets')
            ->select(
                'assigned_to',
                DB::raw('COUNT(*) as total_assigned'),
                DB::raw('COUNT(CASE WHEN status = "Closed" THEN 1 END) as total_resolved'),
                DB::raw('COUNT(CASE WHEN status != "Closed" THEN 1 END) as currently_open')
            )
            ->whereBetween('createdtime', [$startDate, $endDate])
            ->whereNotNull('assigned_to')
            ->groupBy('assigned_to')
            ->get()
            ->map(function ($item) {
                $user = User::find($item->assigned_to);
                $totalAssigned = (int) $item->total_assigned;
                $totalResolved = (int) $item->total_resolved;
                $resolutionPercentage = $totalAssigned > 0 
                    ? number_format(($totalResolved / $totalAssigned) * 100, 2) 
                    : '0.00';

                return [
                    'user_id' => (int) $item->assigned_to,
                    'user_name' => $user ? $user->first_name . ' ' . $user->last_name : 'Unknown',
                    'department' => $user->department ?? 'N/A',
                    'total_assigned' => $totalAssigned,
                    'total_resolved' => $totalResolved,
                    'currently_open' => (int) $item->currently_open,
                    'resolution_percentage' => $resolutionPercentage,
                ];
            });

        // Customer Insights
        $topCustomers = DB::table('tickets')
            ->join('accounts', 'tickets.accountid', '=', 'accounts.accountid')
            ->select(
                'tickets.accountid',
                'accounts.accountname',
                DB::raw('COUNT(*) as ticket_count'),
                DB::raw('COUNT(CASE WHEN tickets.status != "Closed" THEN 1 END) as open_tickets'),
                DB::raw('COUNT(CASE WHEN tickets.priority = "High" OR tickets.priority = "Critical" THEN 1 END) as high_priority_tickets')
            )
            ->whereBetween('tickets.createdtime', [$startDate, $endDate])
            ->groupBy('tickets.accountid', 'accounts.accountname')
            ->orderByDesc('ticket_count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'accountid' => (int) $item->accountid,
                    'accountname' => $item->accountname,
                    'ticket_count' => (int) $item->ticket_count,
                    'open_tickets' => (int) $item->open_tickets,
                    'high_priority_tickets' => (int) $item->high_priority_tickets,
                ];
            });

        $ticketsByCategory = DB::table('tickets')
            ->select('category', DB::raw('COUNT(*) as count'))
            ->whereBetween('createdtime', [$startDate, $endDate])
            ->whereNotNull('category')
            ->groupBy('category')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category,
                    'count' => (int) $item->count,
                ];
            });

        $ticketsBySeverity = DB::table('tickets')
            ->select('severity', DB::raw('COUNT(*) as count'))
            ->whereBetween('createdtime', [$startDate, $endDate])
            ->whereNotNull('severity')
            ->groupBy('severity')
            ->get()
            ->map(function ($item) {
                return [
                    'severity' => $item->severity ?? null,
                    'count' => (int) $item->count,
                ];
            });

        return [
            'kpis' => $kpis,
            'trends' => [
                'tickets_created_vs_resolved' => $ticketTrends,
                'priority_trends' => $priorityTrends,
            ],
            'team_performance' => $teamPerformance,
            'customer_insights' => [
                'top_customers' => $topCustomers,
                'tickets_by_category' => $ticketsByCategory,
                'tickets_by_severity' => $ticketsBySeverity,
            ],
        ];
    }

    /**
     * Get NOC Dashboard Data
     */
    private function getNocDashboardData($user, $startDate, $endDate)
    {
        $criticalMetrics = [
            'total_open_tickets' => (int) Ticket::where('status', 'Open')
                ->whereBetween('createdtime', [$startDate, $endDate])
                ->count(),
            'in_progress_tickets' => (int) Ticket::where('status', 'In Progress')
                ->whereBetween('createdtime', [$startDate, $endDate])
                ->count(),
            'wait_for_response_tickets' => (int) Ticket::where('status', 'Wait For Response')
                ->whereBetween('createdtime', [$startDate, $endDate])
                ->count(),
            'closed_tickets' => (int) Ticket::where('status', 'Closed')
                ->whereBetween('createdtime', [$startDate, $endDate])
                ->count(),
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ];

        $priorityBreakdown = DB::table('tickets')
            ->select('priority', DB::raw('COUNT(*) as count'))
            ->whereBetween('createdtime', [$startDate, $endDate])
            ->groupBy('priority')
            ->get()
            ->map(function ($item) {
                return [
                    'priority' => $item->priority,
                    'count' => (int) $item->count,
                ];
            });

        $severityBreakdown = DB::table('tickets')
            ->select('severity', DB::raw('COUNT(*) as count'))
            ->whereBetween('createdtime', [$startDate, $endDate])
            ->groupBy('severity')
            ->get()
            ->map(function ($item) {
                return [
                    'severity' => $item->severity ?? null,
                    'count' => (int) $item->count,
                ];
            });

        // Team Workload
        $teamWorkload = DB::table('tickets')
            ->select(
                'assigned_to',
                DB::raw('COUNT(CASE WHEN status != "Closed" THEN 1 END) as open_tickets'),
                DB::raw('COUNT(CASE WHEN status = "Closed" AND DATE(modifiedtime) = CURDATE() THEN 1 END) as closed_today')
            )
            ->whereBetween('createdtime', [$startDate, $endDate])
            ->whereNotNull('assigned_to')
            ->groupBy('assigned_to')
            ->get()
            ->map(function ($item) {
                $user = User::find($item->assigned_to);
                $openTickets = (int) $item->open_tickets;
                $workloadIndicator = $openTickets > 20 ? 'high' : ($openTickets > 10 ? 'medium' : 'low');

                return [
                    'user_id' => (int) $item->assigned_to,
                    'user_name' => $user ? $user->first_name . ' ' . $user->last_name : 'Unknown',
                    'department' => $user->department ?? 'NOC',
                    'open_tickets' => $openTickets,
                    'closed_today' => (int) $item->closed_today,
                    'workload_indicator' => $workloadIndicator,
                ];
            });

        // Tickets waiting 24+ hours
        $ticketsWaiting24h = Ticket::where('status', '!=', 'Closed')
            ->whereRaw('TIMESTAMPDIFF(HOUR, createdtime, NOW()) >= 24')
            ->whereBetween('createdtime', [$startDate, $endDate])
            ->limit(20)
            ->get()
            ->map(function ($ticket) {
                $assignedUser = User::find($ticket->assigned_to);
                $hoursWaiting = Carbon::parse($ticket->createdtime)->diffInHours(Carbon::now());

                return [
                    'ticketid' => (int) $ticket->ticketid,
                    'ticket_no' => $ticket->ticket_no,
                    'title' => $ticket->title,
                    'priority' => $ticket->priority,
                    'status' => $ticket->status,
                    'createdtime' => $ticket->createdtime,
                    'hours_waiting' => (int) $hoursWaiting,
                    'assigned_to' => $assignedUser ? $assignedUser->first_name . ' ' . $assignedUser->last_name : 'Unassigned',
                    'accountname' => $ticket->accountname ?? 'N/A',
                ];
            });

        return [
            'critical_metrics' => $criticalMetrics,
            'priority_breakdown' => $priorityBreakdown,
            'severity_breakdown' => $severityBreakdown,
            'team_workload' => $teamWorkload,
            'tickets_waiting_24h' => $ticketsWaiting24h,
            'response_time_metrics' => [], // Implement if needed
        ];
    }

    /**
     * Get Normal Dashboard Data
     */
    private function getNormalDashboardData($user, $startDate, $endDate)
    {
        $myTicketsSummary = [
            'open_tickets' => (int) Ticket::where('assigned_to', $user->id)
                ->where('status', 'Open')
                ->whereBetween('createdtime', [$startDate, $endDate])
                ->count(),
            'in_progress_tickets' => (int) Ticket::where('assigned_to', $user->id)
                ->where('status', 'In Progress')
                ->whereBetween('createdtime', [$startDate, $endDate])
                ->count(),
            'wait_for_response_tickets' => (int) Ticket::where('assigned_to', $user->id)
                ->where('status', 'Wait For Response')
                ->whereBetween('createdtime', [$startDate, $endDate])
                ->count(),
            'closed_tickets' => (int) Ticket::where('assigned_to', $user->id)
                ->where('status', 'Closed')
                ->whereBetween('createdtime', [$startDate, $endDate])
                ->count(),
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ];

        $statusBreakdown = DB::table('tickets')
            ->select('status', DB::raw('COUNT(*) as count'))
            ->where('assigned_to', $user->id)
            ->whereBetween('createdtime', [$startDate, $endDate])
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->status,
                    'count' => (int) $item->count,
                ];
            });

        // Recent Tickets
        $recentTickets = Ticket::where('assigned_to', $user->id)
            ->whereBetween('createdtime', [$startDate, $endDate])
            ->orderByDesc('createdtime')
            ->limit(10)
            ->get()
            ->map(function ($ticket) {
                return [
                    'ticketid' => (int) $ticket->ticketid,
                    'ticket_no' => $ticket->ticket_no,
                    'title' => $ticket->title,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'createdtime' => $ticket->createdtime,
                    'type' => 'ticket',
                ];
            });

        // Recent Comments (simplified - implement based on your comments table)
        $recentComments = [];

        return [
            'my_tickets_summary' => $myTicketsSummary,
            'status_breakdown' => $statusBreakdown,
            'recent_activity' => [
                'recent_tickets' => $recentTickets,
                'recent_comments' => $recentComments,
            ],
        ];
    }

    /**
     * Get Sales Dashboard Data
     */
    private function getSalesDashboardData($user, $startDate, $endDate)
    {
        $salesOverview = [
            'open_tickets' => (int) Ticket::where('status', 'Open')
                ->whereBetween('createdtime', [$startDate, $endDate])
                ->count(),
            'in_progress_tickets' => (int) Ticket::where('status', 'In Progress')
                ->whereBetween('createdtime', [$startDate, $endDate])
                ->count(),
            'wait_for_response_tickets' => (int) Ticket::where('status', 'Wait For Response')
                ->whereBetween('createdtime', [$startDate, $endDate])
                ->count(),
            'closed_tickets' => (int) Ticket::where('status', 'Closed')
                ->whereBetween('createdtime', [$startDate, $endDate])
                ->count(),
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ];

        // High Priority Customers
        $highPriorityCustomers = DB::table('tickets')
            ->join('accounts', 'tickets.accountid', '=', 'accounts.accountid')
            ->select(
                'tickets.accountid',
                'accounts.accountname',
                DB::raw('COUNT(CASE WHEN tickets.priority = "High" OR tickets.priority = "Critical" THEN 1 END) as high_priority_count'),
                DB::raw('COUNT(CASE WHEN tickets.status != "Closed" THEN 1 END) as open_tickets')
            )
            ->whereBetween('tickets.createdtime', [$startDate, $endDate])
            ->groupBy('tickets.accountid', 'accounts.accountname')
            ->havingRaw('high_priority_count > 0')
            ->orderByDesc('high_priority_count')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'accountid' => (int) $item->accountid,
                    'accountname' => $item->accountname,
                    'high_priority_count' => (int) $item->high_priority_count,
                    'open_tickets' => (int) $item->open_tickets,
                ];
            });

        // Customers at Risk
        $customersAtRisk = DB::table('tickets')
            ->join('accounts', 'tickets.accountid', '=', 'accounts.accountid')
            ->select(
                'accounts.accountname',
                DB::raw('COUNT(CASE WHEN tickets.status != "Closed" THEN 1 END) as open_tickets'),
                DB::raw('MAX(DATEDIFF(NOW(), tickets.createdtime)) as oldest_ticket_days'),
                DB::raw('COUNT(CASE WHEN tickets.priority = "Critical" THEN 1 END) as critical_count')
            )
            ->whereBetween('tickets.createdtime', [$startDate, $endDate])
            ->groupBy('accounts.accountname')
            ->havingRaw('open_tickets > 5 OR oldest_ticket_days > 7')
            ->get()
            ->map(function ($item) {
                return [
                    'accountname' => $item->accountname,
                    'open_tickets' => (int) $item->open_tickets,
                    'oldest_ticket_days' => (int) $item->oldest_ticket_days,
                    'critical_count' => (int) $item->critical_count,
                ];
            });

        // Tickets by Organization
        $ticketsByOrganization = DB::table('tickets')
            ->join('accounts', 'tickets.accountid', '=', 'accounts.accountid')
            ->select(
                'tickets.accountid',
                'accounts.accountname',
                'accounts.account_type',
                'accounts.industry',
                DB::raw('COUNT(*) as total_tickets'),
                DB::raw('COUNT(CASE WHEN tickets.status != "Closed" THEN 1 END) as open_tickets'),
                DB::raw('COUNT(CASE WHEN tickets.status = "Closed" THEN 1 END) as closed_tickets'),
                DB::raw('COUNT(CASE WHEN tickets.priority = "High" OR tickets.priority = "Critical" THEN 1 END) as high_priority_tickets'),
                DB::raw('MAX(tickets.createdtime) as last_ticket_date')
            )
            ->whereBetween('tickets.createdtime', [$startDate, $endDate])
            ->groupBy('tickets.accountid', 'accounts.accountname', 'accounts.account_type', 'accounts.industry')
            ->get()
            ->map(function ($item) {
                return [
                    'accountid' => (int) $item->accountid,
                    'accountname' => $item->accountname,
                    'account_type' => $item->account_type,
                    'industry' => $item->industry ?? 'N/A',
                    'total_tickets' => (int) $item->total_tickets,
                    'open_tickets' => (int) $item->open_tickets,
                    'closed_tickets' => (int) $item->closed_tickets,
                    'high_priority_tickets' => (int) $item->high_priority_tickets,
                    'last_ticket_date' => $item->last_ticket_date,
                ];
            });

        // Customer Activity
        $customerActivity = Ticket::whereBetween('createdtime', [$startDate, $endDate])
            ->orderByDesc('createdtime')
            ->limit(20)
            ->get()
            ->map(function ($ticket) {
                $assignedUser = User::find($ticket->assigned_to);
                $healthIndicator = $ticket->priority === 'Critical' ? 'critical' : 
                                  ($ticket->priority === 'High' ? 'warning' : 'normal');

                return [
                    'ticketid' => (int) $ticket->ticketid,
                    'ticket_no' => $ticket->ticket_no,
                    'title' => $ticket->title,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'accountid' => $ticket->accountid ?? null,
                    'accountname' => $ticket->accountname ?? null,
                    'createdtime' => $ticket->createdtime,
                    'assigned_to' => $assignedUser ? $assignedUser->first_name . ' ' . $assignedUser->last_name : null,
                    'health_indicator' => $healthIndicator,
                ];
            });

        // High Value Customers
        $highValueCustomers = DB::table('tickets')
            ->join('accounts', 'tickets.accountid', '=', 'accounts.accountid')
            ->select(
                'tickets.accountid',
                'accounts.accountname',
                'accounts.account_type',
                'accounts.industry',
                DB::raw('COUNT(*) as ticket_activity_score'),
                DB::raw('COUNT(CASE WHEN tickets.status != "Closed" THEN 1 END) as open_tickets')
            )
            ->whereBetween('tickets.createdtime', [$startDate, $endDate])
            ->groupBy('tickets.accountid', 'accounts.accountname', 'accounts.account_type', 'accounts.industry')
            ->orderByDesc('ticket_activity_score')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $rating = $item->ticket_activity_score > 50 ? 'A' : 
                         ($item->ticket_activity_score > 20 ? 'B' : 'C');

                return [
                    'accountid' => (int) $item->accountid,
                    'accountname' => $item->accountname,
                    'account_type' => $item->account_type,
                    'industry' => $item->industry ?? 'N/A',
                    'rating' => $rating,
                    'ticket_activity_score' => (int) $item->ticket_activity_score,
                    'open_tickets' => (int) $item->open_tickets,
                    'status' => (int) $item->open_tickets > 0 ? 'Active' : 'Inactive',
                ];
            });

        return [
            'sales_overview' => $salesOverview,
            'customer_health' => [
                'high_priority_customers' => $highPriorityCustomers,
                'customers_at_risk' => $customersAtRisk,
            ],
            'tickets_by_organization' => $ticketsByOrganization,
            'customer_activity' => $customerActivity,
            'high_value_customers' => $highValueCustomers,
        ];
    }

    /**
     * Get Field Dashboard Data
     */
    private function getFieldDashboardData($user, $startDate, $endDate)
    {
        $kpis = [
            'open_tickets' => (int) Ticket::where('assigned_to', $user->id)
                ->where('status', 'Open')
                ->whereBetween('createdtime', [$startDate, $endDate])
                ->count(),
            'in_progress_tickets' => (int) Ticket::where('assigned_to', $user->id)
                ->where('status', 'In Progress')
                ->whereBetween('createdtime', [$startDate, $endDate])
                ->count(),
            'wait_for_response_tickets' => (int) Ticket::where('assigned_to', $user->id)
                ->where('status', 'Wait For Response')
                ->whereBetween('createdtime', [$startDate, $endDate])
                ->count(),
            'closed_tickets' => (int) Ticket::where('assigned_to', $user->id)
                ->where('status', 'Closed')
                ->whereBetween('createdtime', [$startDate, $endDate])
                ->count(),
            'date_range' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
        ];

        $quickStats = [
            'my_open_tickets' => (int) Ticket::where('assigned_to', $user->id)
                ->where('status', 'Open')
                ->count(),
            'in_progress' => (int) Ticket::where('assigned_to', $user->id)
                ->where('status', 'In Progress')
                ->count(),
            'high_priority' => (int) Ticket::where('assigned_to', $user->id)
                ->whereIn('priority', ['High', 'Critical'])
                ->where('status', '!=', 'Closed')
                ->count(),
            'closed_today' => (int) Ticket::where('assigned_to', $user->id)
                ->where('status', 'Closed')
                ->whereDate('modifiedtime', Carbon::today())
                ->count(),
        ];

        // Today's Schedule (tickets scheduled for today)
        $todaysSchedule = Ticket::where('assigned_to', $user->id)
            ->whereDate('createdtime', Carbon::today())
            ->where('status', '!=', 'Closed')
            ->orderBy('priority', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($ticket) {
                return [
                    'id' => (string) $ticket->ticketid,
                    'ticketNumber' => $ticket->ticket_no,
                    'title' => $ticket->title,
                    'customerName' => $ticket->accountname ?? 'N/A',
                    'location' => $ticket->location ?? 'N/A',
                    'priority' => $ticket->priority,
                    'status' => $ticket->status,
                    'scheduledTime' => $ticket->createdtime,
                ];
            });

        // Recent Comments (implement based on your comments table)
        $recentComments = [];

        return [
            'kpis' => $kpis,
            'quick_stats' => $quickStats,
            'todays_schedule' => $todaysSchedule,
            'recent_comments' => $recentComments,
        ];
    }

    /**
     * Helper: Get role type from user role
     */
    private function getRoleType($role)
    {
        $roleLower = strtolower($role);
        
        if (strpos($roleLower, 'noc') !== false) {
            return 'NOC';
        }
        if (strpos($roleLower, 'field') !== false) {
            return 'Field';
        }
        if (strpos($roleLower, 'management') !== false || 
            strpos($roleLower, 'ceo') !== false ||
            strpos($roleLower, 'manager') !== false) {
            return 'Management';
        }
        if (strpos($roleLower, 'sales') !== false) {
            return 'Sales';
        }
        
        return 'Normal';
    }
}
```

---

## 3. Tickets API

### Endpoint: `GET /tickets`

**Query Parameters:**
- `limit` (integer, default: 50)
- `offset` (integer, default: 0)
- `status` (string, optional)
- `assigned_to` (integer, optional)
- `search` (string, optional)

**Backend Implementation:**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Ticket;

class TicketController extends Controller
{
    /**
     * Get list of tickets
     */
    public function index(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication failed. Please login again.'
                ], 401);
            }

            $query = Ticket::query();

            // Apply filters
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('assigned_to')) {
                $query->where('assigned_to', $request->assigned_to);
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('ticket_no', 'like', "%{$search}%");
                });
            }

            // Get total count before pagination
            $total = $query->count();

            // Apply pagination
            $limit = $request->input('limit', 50);
            $offset = $request->input('offset', 0);
            $tickets = $query->skip($offset)->take($limit)->get();

            // Format response
            $ticketData = $tickets->map(function ($ticket) {
                return [
                    'ticketid' => (int) $ticket->ticketid,
                    'ticket_no' => $ticket->ticket_no,
                    'title' => $ticket->title,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'assigned_to_name' => $ticket->assigned_to_name ?? null,
                    'assigned_to' => $ticket->assigned_to ?? null,
                    'account_name' => $ticket->accountname ?? null,
                    'account' => $ticket->accountname ?? null,
                    'createdtime' => $ticket->createdtime,
                    'modifiedtime' => $ticket->modifiedtime ?? null,
                    'created_time' => $ticket->createdtime,
                    'modified_time' => $ticket->modifiedtime ?? null,
                ];
            });

            return response()->json([
                'success' => true,
                'count' => $ticketData->count(),
                'data' => $ticketData,
                'message' => 'Tickets retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tickets: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a new ticket
     * Endpoint: POST /tickets
     */
    public function store(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication failed. Please login again.'
                ], 401);
            }

            $request->validate([
                'title' => 'required|string',
                'account_id' => 'required|integer',
            ]);

            // Create ticket
            $ticket = new Ticket();
            $ticket->title = $request->title;
            $ticket->description = $request->description ?? '';
            $ticket->solution = $request->solution ?? '';
            $ticket->priority = $request->priority ?? 'Medium';
            $ticket->status = $request->status ?? 'Open';
            $ticket->category = $request->category ?? null;
            $ticket->severity = $request->severity ?? null;
            $ticket->accountid = $request->account_id;
            $ticket->contact_id = $request->contact_id ?? null;
            $ticket->assigned_to = $request->assigned_to ?? null;
            $ticket->hours = $request->hours ?? 0;
            $ticket->days = $request->days ?? 0;
            $ticket->organization_priority = $request->organization_priority ?? null;
            $ticket->services_issue = $request->services_issue ?? null;
            $ticket->issue_end = $request->issue_end ?? null;
            $ticket->created_by = $user->id;
            $ticket->ticket_no = 'TKT-' . str_pad(Ticket::max('ticketid') + 1, 6, '0', STR_PAD_LEFT);
            $ticket->save();

            return response()->json([
                'success' => true,
                'message' => 'Ticket created successfully',
                'data' => [
                    'success' => true,
                    'ticket_id' => (int) $ticket->ticketid,
                    'ticket_no' => $ticket->ticket_no,
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create ticket: ' . $e->getMessage()
            ], 500);
        }
    }
}
```

**Expected Response (GET /tickets):**
```json
{
    "success": true,
    "count": 10,
    "data": [
        {
            "ticketid": 123,
            "ticket_no": "TKT-000123",
            "title": "Network Issue",
            "status": "Open",
            "priority": "High",
            "assigned_to_name": "John Doe",
            "assigned_to": 1,
            "account_name": "ABC Corp",
            "account": "ABC Corp",
            "createdtime": "2024-01-15 10:30:00",
            "modifiedtime": "2024-01-15 11:00:00",
            "created_time": "2024-01-15 10:30:00",
            "modified_time": "2024-01-15 11:00:00"
        }
    ],
    "message": "Tickets retrieved successfully"
}
```

---

## 4. Profile API

### Endpoint: `GET /me`

**Backend Implementation:**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Ticket;

class ProfileController extends Controller
{
    /**
     * Get current user profile
     */
    public function me(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication failed. Please login again.'
                ], 401);
            }

            // Get assigned tickets count
            $assignedTicketsCount = Ticket::where('assigned_to', $user->id)
                ->where('status', '!=', 'Closed')
                ->count();

            $profileData = [
                'id' => (int) $user->id,
                'username' => $user->username,
                'full_name' => trim($user->first_name . ' ' . $user->last_name),
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone ?? '',
                'department' => $user->department ?? '',
                'title' => $user->title ?? '',
                'role' => $user->role,
                'is_admin' => (bool) $user->is_admin,
                'assigned_tickets_count' => (int) $assignedTicketsCount,
                'status' => $user->status ?? 'Active',
            ];

            return response()->json([
                'success' => true,
                'data' => $profileData,
                'message' => 'Profile retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch profile: ' . $e->getMessage()
            ], 500);
        }
    }
}
```

**Expected Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "username": "admin",
        "full_name": "ConnecTEL Private Limited",
        "first_name": "ConnecTEL",
        "last_name": "Private Limited",
        "email": "noc@conectel.com.pk",
        "phone": "+1234567890",
        "department": "IT",
        "title": "CEO",
        "role": "CEO",
        "is_admin": true,
        "assigned_tickets_count": 5,
        "status": "Active"
    },
    "message": "Profile retrieved successfully"
}
```

---

## 5. Comments API

### Endpoint: `GET /tickets/{id}/comments`

**Backend Implementation:**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Comment;
use App\Models\User;

class CommentController extends Controller
{
    /**
     * Get comments for a ticket
     */
    public function index(Request $request, $ticketId)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication failed. Please login again.'
                ], 401);
            }

            $comments = Comment::where('ticketid', $ticketId)
                ->orderBy('createdtime', 'asc')
                ->get();

            $commentData = $comments->map(function ($comment) {
                $createdBy = User::find($comment->ownerid);
                
                return [
                    'commentid' => (int) $comment->commentid,
                    'ticketid' => (int) $comment->ticketid,
                    'comments' => $comment->comments,
                    'ownerid' => (int) $comment->ownerid,
                    'ownertype' => $comment->ownertype,
                    'createdtime' => $comment->createdtime,
                    'created_by_name' => $createdBy ? $createdBy->first_name . ' ' . $createdBy->last_name : 'Unknown',
                    'created_by_email' => $createdBy->email ?? null,
                ];
            });

            return response()->json([
                'success' => true,
                'count' => $commentData->count(),
                'data' => $commentData,
                'message' => 'Comments retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch comments: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add comment to ticket
     * Endpoint: POST /tickets/{id}/comments
     */
    public function store(Request $request, $ticketId)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication failed. Please login again.'
                ], 401);
            }

            $request->validate([
                'comment' => 'required|string',
            ]);

            $comment = new Comment();
            $comment->ticketid = $ticketId;
            $comment->comments = $request->comment;
            $comment->ownerid = $user->id;
            $comment->ownertype = 'Users';
            $comment->createdtime = now()->format('Y-m-d H:i:s');
            $comment->save();

            // Return all comments for the ticket
            $comments = Comment::where('ticketid', $ticketId)
                ->orderBy('createdtime', 'asc')
                ->get();

            $commentData = $comments->map(function ($comment) {
                $createdBy = User::find($comment->ownerid);
                
                return [
                    'commentid' => (int) $comment->commentid,
                    'ticketid' => (int) $comment->ticketid,
                    'comments' => $comment->comments,
                    'ownerid' => (int) $comment->ownerid,
                    'ownertype' => $comment->ownertype,
                    'createdtime' => $comment->createdtime,
                    'created_by_name' => $createdBy ? $createdBy->first_name . ' ' . $createdBy->last_name : 'Unknown',
                    'created_by_email' => $createdBy->email ?? null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully',
                'data' => $commentData
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment: ' . $e->getMessage()
            ], 500);
        }
    }
}
```

**Expected Response:**
```json
{
    "success": true,
    "count": 2,
    "data": [
        {
            "commentid": 1,
            "ticketid": 123,
            "comments": "Working on this issue",
            "ownerid": 1,
            "ownertype": "Users",
            "createdtime": "2024-01-15 10:30:00",
            "created_by_name": "John Doe",
            "created_by_email": "john@example.com"
        }
    ],
    "message": "Comments retrieved successfully"
}
```

---

## 6. Notifications API

### Endpoint: `GET /notifications`

**Query Parameters:**
- `related_type` (string, optional: 'ticket', 'comment', 'other')
- `is_read` (integer, optional: 0 or 1)
- `limit` (integer, default: 20)
- `offset` (integer, default: 0)

**Backend Implementation:**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Notification;

class NotificationController extends Controller
{
    /**
     * Get list of notifications for authenticated user
     */
    public function index(Request $request)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication failed. Please login again.'
                ], 401);
            }

            $query = Notification::where('user_id', $user->id);

            // Apply filters
            if ($request->has('related_type')) {
                $query->where('related_type', $request->related_type);
            }

            if ($request->has('is_read')) {
                $query->where('is_read', $request->is_read);
            }

            // Get total count
            $total = $query->count();

            // Apply pagination
            $limit = $request->input('limit', 20);
            $offset = $request->input('offset', 0);
            $notifications = $query->skip($offset)->take($limit)
                ->orderByDesc('created_at')
                ->get();

            $notificationData = $notifications->map(function ($notification) {
                return [
                    'id' => (int) $notification->id,
                    'user_id' => (int) $notification->user_id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'related_id' => $notification->related_id ?? null,
                    'related_type' => $notification->related_type,
                    'is_read' => (int) $notification->is_read,
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $notification->updated_at ? $notification->updated_at->format('Y-m-d H:i:s') : null,
                ];
            });

            return response()->json([
                'success' => true,
                'count' => $notificationData->count(),
                'total' => $total,
                'data' => $notificationData,
                'message' => 'Notifications retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch notifications: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark notification as read
     * Endpoint: PUT /notifications/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication failed. Please login again.'
                ], 401);
            }

            $notification = Notification::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$notification) {
                return response()->json([
                    'success' => false,
                    'message' => 'Notification not found'
                ], 404);
            }

            $notification->is_read = 1;
            $notification->save();

            return response()->json([
                'success' => true,
                'message' => 'Notification marked as read',
                'data' => [
                    'id' => (int) $notification->id,
                    'user_id' => (int) $notification->user_id,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'related_id' => $notification->related_id ?? null,
                    'related_type' => $notification->related_type,
                    'is_read' => (int) $notification->is_read,
                    'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $notification->updated_at ? $notification->updated_at->format('Y-m-d H:i:s') : null,
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification: ' . $e->getMessage()
            ], 500);
        }
    }
}
```

**Expected Response:**
```json
{
    "success": true,
    "count": 5,
    "total": 10,
    "data": [
        {
            "id": 1,
            "user_id": 1,
            "title": "New Ticket Assigned",
            "message": "You have been assigned to ticket TKT-000123",
            "related_id": 123,
            "related_type": "ticket",
            "is_read": 0,
            "created_at": "2024-01-15 10:30:00",
            "updated_at": null
        }
    ],
    "message": "Notifications retrieved successfully"
}
```

---

## 7. Ticket Detail API

### Endpoint: `GET /tickets/{id}`

**Backend Implementation:**

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\Ticket;
use App\Models\Comment;
use App\Models\Account;

class TicketDetailController extends Controller
{
    /**
     * Get ticket details by ID
     */
    public function show(Request $request, $id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication failed. Please login again.'
                ], 401);
            }

            $ticket = Ticket::find($id);

            if (!$ticket) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ticket not found'
                ], 404);
            }

            // Get comments
            $comments = Comment::where('ticketid', $id)
                ->orderBy('createdtime', 'desc')
                ->get();

            $allComments = $comments->map(function ($comment) {
                $createdBy = User::find($comment->ownerid);
                return [
                    'id' => (string) $comment->commentid,
                    'author' => $createdBy ? $createdBy->first_name . ' ' . $createdBy->last_name : 'Unknown',
                    'text' => $comment->comments,
                    'timestamp' => $comment->createdtime,
                ];
            });

            $lastComment = $allComments->first();

            // Get organization details if accountid exists
            $organizationDetails = null;
            if ($ticket->accountid) {
                $account = Account::find($ticket->accountid);
                if ($account) {
                    $organizationDetails = [
                        'name' => $account->accountname,
                        'phone' => $account->phone ?? '',
                        'email' => $account->email ?? '',
                        'website' => $account->website ?? '',
                        'billing_address' => [
                            'street' => $account->bill_street ?? '',
                            'city' => $account->bill_city ?? '',
                            'state' => $account->bill_state ?? '',
                            'country' => $account->bill_country ?? '',
                            'postal_code' => $account->bill_code ?? '',
                        ],
                        'shipping_address' => [
                            'street' => $account->ship_street ?? '',
                            'city' => $account->ship_city ?? '',
                            'state' => $account->ship_state ?? '',
                            'country' => $account->ship_country ?? '',
                            'postal_code' => $account->ship_code ?? '',
                        ],
                    ];
                }
            }

            $ticketDetailData = [
                'summary' => [
                    'ticket_id' => (int) $ticket->ticketid,
                    'ticket_no' => $ticket->ticket_no,
                    'title' => $ticket->title,
                    'status' => $ticket->status,
                    'priority' => $ticket->priority,
                    'severity' => $ticket->severity ?? null,
                    'category' => $ticket->category ?? null,
                    'assigned_to' => $ticket->assigned_to_name ?? 'Unassigned',
                    'created_by' => $ticket->created_by_name ?? 'Unknown',
                    'account' => $ticket->accountname ?? 'N/A',
                    'contact' => $ticket->contact_name ?? null,
                    'created_time' => $ticket->createdtime,
                    'modified_time' => $ticket->modifiedtime ?? $ticket->createdtime,
                ],
                'details' => [
                    'description' => $ticket->description ?? '',
                    'solution' => $ticket->solution ?? '',
                    'hours' => (int) ($ticket->hours ?? 0),
                    'days' => (int) ($ticket->days ?? 0),
                    'custom_fields' => [], // Implement based on your custom fields
                    'custom_fields_labeled' => [], // Implement based on your custom fields
                ],
                'last_comment' => $lastComment,
                'all_comments' => $allComments,
            ];

            if ($organizationDetails) {
                $ticketDetailData['organization_details'] = $organizationDetails;
            }

            return response()->json([
                'success' => true,
                'data' => $ticketDetailData,
                'message' => 'Ticket details retrieved successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch ticket details: ' . $e->getMessage()
            ], 500);
        }
    }
}
```

**Expected Response:**
```json
{
    "success": true,
    "data": {
        "summary": {
            "ticket_id": 123,
            "ticket_no": "TKT-000123",
            "title": "Network Issue",
            "status": "Open",
            "priority": "High",
            "severity": "Major",
            "category": "Network",
            "assigned_to": "John Doe",
            "created_by": "Admin",
            "account": "ABC Corp",
            "contact": "Jane Smith",
            "created_time": "2024-01-15 10:30:00",
            "modified_time": "2024-01-15 11:00:00"
        },
        "details": {
            "description": "Network connection is down",
            "solution": "",
            "hours": 0,
            "days": 0,
            "custom_fields": {},
            "custom_fields_labeled": {}
        },
        "last_comment": {
            "id": "1",
            "author": "John Doe",
            "text": "Working on it",
            "timestamp": "2024-01-15 11:00:00"
        },
        "all_comments": [
            {
                "id": "1",
                "author": "John Doe",
                "text": "Working on it",
                "timestamp": "2024-01-15 11:00:00"
            }
        ],
        "organization_details": {
            "name": "ABC Corp",
            "phone": "+1234567890",
            "email": "contact@abccorp.com",
            "website": "https://abccorp.com",
            "billing_address": {
                "street": "123 Main St",
                "city": "New York",
                "state": "NY",
                "country": "USA",
                "postal_code": "10001"
            },
            "shipping_address": {
                "street": "123 Main St",
                "city": "New York",
                "state": "NY",
                "country": "USA",
                "postal_code": "10001"
            }
        }
    },
    "message": "Ticket details retrieved successfully"
}
```

---

## 8. Common Response Helpers

**Create a helper trait for consistent responses:**

```php
<?php

namespace App\Traits;

trait ApiResponse
{
    /**
     * Return success response
     */
    protected function successResponse($data, $message = 'Success', $statusCode = 200)
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Return error response
     */
    protected function errorResponse($message, $statusCode = 400)
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $statusCode);
    }

    /**
     * Return authentication error
     */
    protected function authError()
    {
        return response()->json([
            'success' => false,
            'message' => 'Authentication failed. Please login again.'
        ], 401);
    }
}
```

---

## Important Notes

1. **All responses MUST include `success` field** (boolean)
2. **Use snake_case for all field names** (e.g., `ticket_no`, `createdtime`, `account_name`)
3. **Numbers must be integers**, not strings (e.g., `"count": 5` not `"count": "5"`)
4. **Dates must be strings** in format: `"YYYY-MM-DD HH:mm:ss"` or `"YYYY-MM-DD"`
5. **Authentication**: All protected endpoints require `Authorization: Bearer {token}` header
6. **Error responses**: Return HTTP status codes (401, 403, 404, 500) with `success: false` and `message` field
7. **Role Type Mapping**: The `role_info.type` in login response is CRITICAL for dashboard routing

---

## Testing Checklist

After implementing, test each endpoint:

- [ ] Login returns `role_info.type`
- [ ] Dashboard returns role-specific data structure
- [ ] Tickets list returns correct format
- [ ] Profile returns all required fields
- [ ] Comments return with `created_by_name`
- [ ] Notifications filter correctly
- [ ] Ticket detail includes all sections
- [ ] All responses have `success` field
- [ ] All numbers are integers, not strings
- [ ] All dates are in correct format

---

**This implementation matches exactly what the frontend expects. Use this code as a reference for your backend development.**

