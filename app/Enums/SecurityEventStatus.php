<?php

// app/Enums/EventStatus.php

namespace App\Enums;

enum SecurityEventStatus: string
{
    // --- Initial States ---
    case NEW = 'new';
    // Description: Event has just been received or created by the system.
    // Action: Requires initial operator review, triage, and acknowledgment. This is the entry point for most events.

    case QUEUED_FOR_PROCESSING = 'queued_for_processing';
    // Description: Event has been received but is waiting in a queue for initial automated or manual processing/parsing.
    // Action: System will pick it up; operators might see this for very brief periods during high load.

    // --- Operator Interaction / Investigation States ---
    case ACKNOWLEDGED = 'acknowledged';
    // Description: An operator has seen and taken ownership/responsibility for the event.
    // Action: Operator will now investigate further or initiate standard operating procedures.

    case INVESTIGATING = 'investigating';
    // Description: Operator is actively researching the event, checking history, camera feeds, or site details.
    // Action: Information gathering phase before deciding on the next action.

    case INFORMATION_REQUESTED = 'information_requested';
    // Description: Operator has requested more information (e.g., from site contact, field technician).
    // Action: Waiting for the requested information to proceed.

    // --- Actioning / Dispatch States ---
    case ACTION_PENDING_VERIFICATION = 'action_pending_verification';
    // Description: Event appears to require action (e.g., dispatch), but verification calls are being made first.
    // Action: Operator is attempting to contact site keyholders/contacts.

    case ACTION_PENDING_DISPATCH = 'action_pending_dispatch';
    // Description: Verification attempts (if any) are complete or per protocol, and dispatch is being initiated.
    // Action: Operator is contacting relevant authorities or guard services.

    case DISPATCHED_AUTHORITIES = 'dispatched_authorities';
    // Description: Police, Fire, or EMS have been successfully contacted and dispatched to the site.
    // Action: Awaiting updates from the dispatched authorities. Operator may monitor.

    case DISPATCHED_GUARD = 'dispatched_guard';
    // Description: A private guard service has been successfully contacted and dispatched.
    // Action: Awaiting updates from the dispatched guard service. Operator may monitor.

    case DISPATCH_REFUSED_BY_AGENCY = 'dispatch_refused_by_agency';
    // Description: Authorities or guard service declined to dispatch (e.g., due to false alarm history, policy).
    // Action: Operator needs to log reason and potentially take alternative actions or notify site.

    // --- Site Interaction States ---
    case SITE_CONTACT_IN_PROGRESS = 'site_contact_in_progress';
    // Description: Operator is currently attempting to contact the site or designated keyholders.
    // Action: Actively dialing or awaiting connection.

    case SITE_CONTACTED_AWAITING_INFO = 'site_contacted_awaiting_info';
    // Description: Communication established with site contact; waiting for them to provide information or take action.
    // Action: Operator is on standby for an update from the site.

    case SITE_ADVISED_NO_ACTION = 'site_advised_no_action';
    // Description: Site contact was reached and advised no further action (e.g., dispatch) is required by CSR.
    // Action: Operator may proceed to close event with appropriate resolution code.

    // --- Waiting / Holding States ---
    case AWAITING_AUTHORITY_RESPONSE = 'awaiting_authority_response';
    // Description: Dispatched authorities are on site or en route; awaiting their findings or "all clear".
    // Action: Operator monitors; may have a timer for follow-up.

    case AWAITING_GUARD_RESPONSE = 'awaiting_guard_response';
    // Description: Dispatched guard service is on site or en route; awaiting their report.
    // Action: Operator monitors; may have a timer for follow-up.

    case ON_HOLD_BY_OPERATOR = 'on_hold_by_operator';
    // Description: Operator has temporarily put the event on hold (e.g., awaiting supervisor input, system issue).
    // Action: Requires operator to manually take it off hold or a timer to re-alert.

    // --- Cancellation States ---
    case CANCELLED_BY_SITE_PRE_DISPATCH = 'cancelled_by_site_pre_dispatch';
    // Description: Site contact provided a valid cancel/abort signal before any dispatch was made.
    // Action: Event is typically closed as a false alarm due to user action.

    case CANCELLED_BY_SITE_POST_DISPATCH = 'cancelled_by_site_post_dispatch';
    // Description: Site contact requested cancellation after dispatch was already made.
    // Action: Operator attempts to cancel dispatch with agency; may still incur charges/response.

    case CANCELLED_BY_OPERATOR_PROCEDURE = 'cancelled_by_operator_procedure';
    // Description: Operator cancelled the event based on established procedures (e.g., clear test signal).
    // Action: Event closed, reason documented.

    // --- Resolution / Closing States ---
    case RESOLVED_PENDING_CLOSURE = 'resolved_pending_closure';
    // Description: The primary actions are complete, and a resolution outcome is determined, awaiting final closing steps.
    // Action: Operator applies final resolution code and notes.

    case CLOSED_CONFIRMED_ACTUAL = 'closed_confirmed_actual';
    // Description: Event outcome confirmed as a genuine incident (e.g., burglary occurred, fire confirmed).
    // Action: All necessary reports filed, event archived.

    case CLOSED_CONFIRMED_FALSE = 'closed_confirmed_false';
    // Description: Event outcome confirmed as a false alarm (user error, equipment, environmental).
    // Action: All necessary reports filed, event archived. May trigger false alarm tracking.

    case CLOSED_TEST_VERIFIED = 'closed_test_verified';
    // Description: Test signal received, verified as legitimate, and system functioning as expected.
    // Action: Event logged and archived.

    case CLOSED_INFORMATIONAL_LOGGED = 'closed_informational_logged';
    // Description: Informational event (e.g., opening/closing) processed and logged, no further action needed.
    // Action: Event archived.

    case CLOSED_TROUBLE_CLEARED = 'closed_trouble_cleared';
    // Description: A system trouble condition has been acknowledged and has since cleared or been addressed.
    // Action: Event archived.

    case CLOSED_SERVICE_COMPLETED = 'closed_service_completed';
    // Description: A requested service or maintenance action has been completed.
    // Action: Event archived.

    case CLOSED_UNABLE_TO_VERIFY = 'closed_unable_to_verify';
    // Description: Attempts to verify the event with site or authorities were unsuccessful after due diligence.
    // Action: Event closed with details of attempts made, logged as per protocol.

    case CLOSED_DUPLICATE = 'closed_duplicate';
    // Description: This event is a duplicate of another already being handled.
    // Action: Linked to the primary event and closed.

    // --- Escalation / Special Handling ---
    case ESCALATED_TO_SUPERVISOR = 'escalated_to_supervisor';
    // Description: Event requires supervisor attention or override.
    // Action: Supervisor queue; original operator may be awaiting instructions.

    case PENDING_EXTERNAL_SYSTEM_UPDATE = 'pending_external_system_update';
    // Description: Waiting for an update from an integrated external system (e.g., video verification platform, technician dispatch system).
    // Action: System or operator monitors for the external update.

    // --- Automated System States ---
    case AUTO_QUEUED_FOR_RESOLUTION = 'auto_queued_for_resolution';
    // Description: Event has been flagged by the system as potentially resolvable by automated rules.
    // Action: Automated rule engine will attempt to process and close.

    case AUTO_RESOLVED_SUCCESS = 'auto_resolved_success';
    // Description: Event was successfully processed and closed by automated system rules.
    // Action: Logged and archived, typically no operator intervention needed.

    case AUTO_RESOLUTION_FAILED_NEEDS_REVIEW = 'auto_resolution_failed_needs_review';
    // Description: Automated system attempted to resolve but failed; requires manual operator review.
    // Action: Event moved to an operator queue (likely 'NEW' or a specific review queue).

    /**
     * Provides a human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::NEW => 'New',
            self::QUEUED_FOR_PROCESSING => 'Queued for Processing',
            self::ACKNOWLEDGED => 'Acknowledged',
            self::INVESTIGATING => 'Investigating',
            self::INFORMATION_REQUESTED => 'Information Requested',
            self::ACTION_PENDING_VERIFICATION => 'Pending Verification Calls',
            self::ACTION_PENDING_DISPATCH => 'Pending Dispatch',
            self::DISPATCHED_AUTHORITIES => 'Authorities Dispatched',
            self::DISPATCHED_GUARD => 'Guard Dispatched',
            self::DISPATCH_REFUSED_BY_AGENCY => 'Dispatch Refused by Agency',
            self::SITE_CONTACT_IN_PROGRESS => 'Site Contact in Progress',
            self::SITE_CONTACTED_AWAITING_INFO => 'Site Contacted - Awaiting Info',
            self::SITE_ADVISED_NO_ACTION => 'Site Advised No Action',
            self::AWAITING_AUTHORITY_RESPONSE => 'Awaiting Authority Response',
            self::AWAITING_GUARD_RESPONSE => 'Awaiting Guard Response',
            self::ON_HOLD_BY_OPERATOR => 'On Hold by Operator',
            self::CANCELLED_BY_SITE_PRE_DISPATCH => 'Cancelled by Site (Pre-Dispatch)',
            self::CANCELLED_BY_SITE_POST_DISPATCH => 'Cancelled by Site (Post-Dispatch)',
            self::CANCELLED_BY_OPERATOR_PROCEDURE => 'Cancelled by Operator (Procedure)',
            self::RESOLVED_PENDING_CLOSURE => 'Resolved - Pending Closure',
            self::CLOSED_CONFIRMED_ACTUAL => 'Closed - Confirmed Actual',
            self::CLOSED_CONFIRMED_FALSE => 'Closed - Confirmed False',
            self::CLOSED_TEST_VERIFIED => 'Closed - Test Verified',
            self::CLOSED_INFORMATIONAL_LOGGED => 'Closed - Informational Logged',
            self::CLOSED_TROUBLE_CLEARED => 'Closed - Trouble Cleared',
            self::CLOSED_SERVICE_COMPLETED => 'Closed - Service Completed',
            self::CLOSED_UNABLE_TO_VERIFY => 'Closed - Unable to Verify',
            self::CLOSED_DUPLICATE => 'Closed - Duplicate',
            self::ESCALATED_TO_SUPERVISOR => 'Escalated to Supervisor',
            self::PENDING_EXTERNAL_SYSTEM_UPDATE => 'Pending External System Update',
            self::AUTO_QUEUED_FOR_RESOLUTION => 'Auto - Queued for Resolution',
            self::AUTO_RESOLVED_SUCCESS => 'Auto - Resolved Successfully',
            self::AUTO_RESOLUTION_FAILED_NEEDS_REVIEW => 'Auto - Resolution Failed (Needs Review)',
        };
    }

    /**
     * Get statuses that are generally considered "open" and actively require operator attention or monitoring.
     * This list is crucial for operator queues and dashboards.
     */
    public static function getOpenWorkflowStatuses(): array
    {
        return [
            self::NEW,
            self::QUEUED_FOR_PROCESSING, // if operators need to see this queue
            self::ACKNOWLEDGED,
            self::INVESTIGATING,
            self::INFORMATION_REQUESTED,
            self::ACTION_PENDING_VERIFICATION,
            self::ACTION_PENDING_DISPATCH,
            self::DISPATCHED_AUTHORITIES, // Open until outcome known
            self::DISPATCHED_GUARD,       // Open until outcome known
            self::SITE_CONTACT_IN_PROGRESS,
            self::SITE_CONTACTED_AWAITING_INFO,
            self::AWAITING_AUTHORITY_RESPONSE,
            self::AWAITING_GUARD_RESPONSE,
            self::ON_HOLD_BY_OPERATOR,
            self::ESCALATED_TO_SUPERVISOR,
            self::PENDING_EXTERNAL_SYSTEM_UPDATE,
            self::AUTO_RESOLUTION_FAILED_NEEDS_REVIEW,
        ];
    }

    /**
     * Get statuses that indicate a dispatch has occurred or is in progress.
     */
    public static function getDispatchRelatedStatuses(): array
    {
        return [
            self::ACTION_PENDING_DISPATCH,
            self::DISPATCHED_AUTHORITIES,
            self::DISPATCHED_GUARD,
            self::AWAITING_AUTHORITY_RESPONSE,
            self::AWAITING_GUARD_RESPONSE,
        ];
    }

    /**
     * Get statuses that are considered "closed" or terminal.
     */
    public static function getClosedTerminalStatuses(): array
    {
        return [
            self::CLOSED_CONFIRMED_ACTUAL,
            self::CLOSED_CONFIRMED_FALSE,
            self::CLOSED_TEST_VERIFIED,
            self::CLOSED_INFORMATIONAL_LOGGED,
            self::CLOSED_TROUBLE_CLEARED,
            self::CLOSED_SERVICE_COMPLETED,
            self::CLOSED_UNABLE_TO_VERIFY,
            self::CLOSED_DUPLICATE,
            self::AUTO_RESOLVED_SUCCESS,
            self::CANCELLED_BY_SITE_PRE_DISPATCH, // often considered closed
            self::CANCELLED_BY_OPERATOR_PROCEDURE, // often considered closed
        ];
    }
}
