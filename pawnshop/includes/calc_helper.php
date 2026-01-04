<?php
/**
 * Calculate principal, interest, penalty, and total due
 * for a pawn transaction.
 *
 * @param float  $loan_amount        Original loan/principal
 * @param float  $interest_rate      Monthly interest rate (%)
 * @param float  $penalty_rate       Penalty rate on total due beyond grace (%)
 * @param string $transaction_date   YYYY-MM-DD (start date)
 * @param string $grace_period_end   YYYY-MM-DD (end of grace)
 * @param string $payment_date       YYYY-MM-DD (when paying)
 *
 * @return array {
 *   months, principal, interest_total, penalty, total_due
 * }
 */
function calculate_charges(
    float $loan_amount,
    float $interest_rate,
    float $penalty_rate,
    string $transaction_date,
    string $grace_period_end,
    string $payment_date
): array {
    $start = new DateTime($transaction_date);
    $pay   = new DateTime($payment_date);

    // Number of months (rounded up if there are extra days)
    $diff   = $start->diff($pay);
    $months = max(1, $diff->y * 12 + $diff->m + ($diff->d > 0 ? 1 : 0));

    $monthly_interest = $loan_amount * ($interest_rate / 100);
    $interest_total   = $monthly_interest * $months;
    $principal        = $loan_amount;
    $total_no_penalty = $principal + $interest_total;

    $penalty = 0;
    if ($pay > new DateTime($grace_period_end)) {
        $penalty = $total_no_penalty * ($penalty_rate / 100);
    }

    $total_due = $total_no_penalty + $penalty;

    return [
        'months'         => $months,
        'principal'      => round($principal, 2),
        'interest_total' => round($interest_total, 2),
        'penalty'        => round($penalty, 2),
        'total_due'      => round($total_due, 2),
    ];
}