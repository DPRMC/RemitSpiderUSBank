<?php


namespace DPRMC\RemitSpiderCTSLink\Eloquent;

use Illuminate\Database\Eloquent\Model;

/**
 *
 */
class CustodianUsBankCmbsCrefc extends Model {

    public $table        = 'custodian_usbank_cmbs_crefcs';
    public $primaryKey   = self::id;
    public $keyType      = 'integer';
    public $incrementing = TRUE;

    const id         = 'id';
    const created_at = 'created_at';
    const updated_at = 'updated_at';
    const shelf      = 'shelf';
    const series     = 'series';

    const transaction_id                              = 'transaction_id';
    const group_id                                    = 'group_id';
    const loan_id                                     = 'loan_id';
    const prospectus_loan_id                          = 'prospectus_loan_id';
    const original_note_amount                        = 'original_note_amount';
    const original_term_of_loan                       = 'original_term_of_loan';
    const original_amortization_term                  = 'original_amortization_term';
    const original_note_rate                          = 'original_note_rate';
    const original_payment_rate                       = 'original_payment_rate';
    const first_loan_payment_due_date                 = 'first_loan_payment_due_date';
    const grace_days_allowed                          = 'grace_days_allowed';
    const interest_only_y_n                           = 'interest_only_y_n';
    const balloon_y_n                                 = 'balloon_y_n';
    const interest_rate_type                          = 'interest_rate_type';
    const interest_accrual_method                     = 'interest_accrual_method';
    const interest_in_arrears_y_n                     = 'interest_in_arrears_y_n';
    const payment_type                                = 'payment_type';
    const prepayment_lock_out_end_date                = 'prepayment_lock_out_end_date';
    const yield_maintenance_end_date                  = 'yield_maintenance_end_date';
    const prepayment_premium_end_date                 = 'prepayment_premium_end_date';
    const prepayment_terms_description                = 'prepayment_terms_description';
    const arm_index                                   = 'arm_index';
    const first_rate_adjustment_date                  = 'first_rate_adjustment_date';
    const first_payment_adjustment_date               = 'first_payment_adjustment_date';
    const arm_margin                                  = 'arm_margin';
    const lifetime_rate_cap                           = 'lifetime_rate_cap';
    const lifetime_rate_floor                         = 'lifetime_rate_floor';
    const periodic_rate_increase_limit                = 'periodic_rate_increase_limit';
    const periodic_rate_decrease_limit                = 'periodic_rate_decrease_limit';
    const periodic_pay_adjustment_max                 = 'periodic_pay_adjustment_max';
    const payment_frequency                           = 'payment_frequency';
    const rate_reset_frequency                        = 'rate_reset_frequency';
    const pay_reset_frequency                         = 'pay_reset_frequency';
    const rounding_code                               = 'rounding_code';
    const rounding_increment                          = 'rounding_increment';
    const index_look_back_in_days                     = 'index_look_back_in_days';
    const negative_amortization_allowed_y_n           = 'negative_amortization_allowed_y_n';
    const max_neg_allowed_of_orig_bal                 = 'max_neg_allowed_of_orig_bal';
    const maximum_negate_allowed                      = 'maximum_negate_allowed';
    const remaining_term_at_contribution              = 'remaining_term_at_contribution';
    const remaining_amort_term_at_contribution        = 'remaining_amort_term_at_contribution';
    const maturity_date_at_contribution               = 'maturity_date_at_contribution';
    const scheduled_principal_balance_at_contribution = 'scheduled_principal_balance_at_contribution';
    const note_rate_at_contribution                   = 'note_rate_at_contribution';
    const servicer_and_trustee_fee_rate               = 'servicer_and_trustee_fee_rate';
    const fee_rate_strip_rate_1                       = 'fee_rate_strip_rate_1';
    const fee_rate_strip_rate_2                       = 'fee_rate_strip_rate_2';
    const fee_rate_strip_rate_3                       = 'fee_rate_strip_rate_3';
    const fee_rate_strip_rate_4                       = 'fee_rate_strip_rate_4';
    const fee_rate_strip_rate_5                       = 'fee_rate_strip_rate_5';
    const net_rate_at_contribution                    = 'net_rate_at_contribution';
    const periodic_p_i_payment_at_contribution        = 'periodic_p_i_payment_at_contribution';
    const number_of_properties_at_contribution        = 'number_of_properties_at_contribution';
    const property_name                               = 'property_name';
    const property_address                            = 'property_address';
    const property_city                               = 'property_city';
    const property_state                              = 'property_state';
    const property_zip_code                           = 'property_zip_code';
    const property_county                             = 'property_county';
    const property_type                               = 'property_type';
    const net_rentable_square_feet_at_contribution    = 'net_rentable_square_feet_at_contribution';
    const number_of_units_beds_rooms_at_contribution  = 'number_of_units_beds_rooms_at_contribution';
    const year_built                                  = 'year_built';
    const noi_at_contribution                         = 'noi_at_contribution';
    const dscr_noi_at_contribution                    = 'dscr_noi_at_contribution';
    const valuation_amount_at_contribution            = 'valuation_amount_at_contribution';
    const valuation_date_at_contribution              = 'valuation_date_at_contribution';
    const physical_occupancy_at_contribution          = 'physical_occupancy_at_contribution';
    const revenue_at_contribution                     = 'revenue_at_contribution';
    const operating_expenses_at_contribution          = 'operating_expenses_at_contribution';
    const contribution_financials_as_of_date          = 'contribution_financials_as_of_date';
    const recourse_y_n                                = 'recourse_y_n';
    const empty_field_fka_ground_lease_y_s_n          = 'empty_field_fka_ground_lease_y_s_n';
    const cross_collateralized_loan_grouping          = 'cross_collateralized_loan_grouping';
    const collection_of_escrow_y_n                    = 'collection_of_escrow_y_n';
    const collection_of_other_reserves_y_n            = 'collection_of_other_reserves_y_n';
    const lien_position_at_contribution               = 'lien_position_at_contribution';
    const current_hyper_amortizing_date               = 'current_hyper_amortizing_date';
    const defeasance_option_start_date                = 'defeasance_option_start_date';
    const empty_field_fka_defeasance_option_end_date  = 'empty_field_fka_defeasance_option_end_date';
    const last_setup_change_date                      = 'last_setup_change_date';
    const ncf_at_contribution                         = 'ncf_at_contribution';
    const dscr_ncf_at_contribution                    = 'dscr_ncf_at_contribution';
    const dscr_indicator_at_contribution              = 'dscr_indicator_at_contribution';
    const loan_contributor_to_securitization          = 'loan_contributor_to_securitization';
    const credit_tenant_lease_y_n                     = 'credit_tenant_lease_y_n';
    const financial_information_submission_penalties  = 'financial_information_submission_penalties';
    const additional_financing_indicator              = 'additional_financing_indicator';
    const loan_structure                              = 'loan_structure';
    const origination_date                            = 'origination_date';
    const original_interest_only_term                 = 'original_interest_only_term';
    const underwriting_indicator                      = 'underwriting_indicator';
    const servicing_advance_methodology               = 'servicing_advance_methodology';
    const valuation_source_at_contribution            = 'valuation_source_at_contribution';

    protected $casts = [
        self::shelf                                       => 'string',
        self::series                                      => 'string',
        self::transaction_id                              => 'string',
        self::group_id                                    => 'string',
        self::loan_id                                     => 'string',
        self::prospectus_loan_id                          => 'string',
        self::original_note_amount                        => 'string',
        self::original_term_of_loan                       => 'string',
        self::original_amortization_term                  => 'string',
        self::original_note_rate                          => 'string',
        self::original_payment_rate                       => 'string',
        self::first_loan_payment_due_date                 => 'string',
        self::grace_days_allowed                          => 'string',
        self::interest_only_y_n                           => 'string',
        self::balloon_y_n                                 => 'string',
        self::interest_rate_type                          => 'string',
        self::interest_accrual_method                     => 'string',
        self::interest_in_arrears_y_n                     => 'string',
        self::payment_type                                => 'string',
        self::prepayment_lock_out_end_date                => 'string',
        self::yield_maintenance_end_date                  => 'string',
        self::prepayment_premium_end_date                 => 'string',
        self::prepayment_terms_description                => 'string',
        self::arm_index                                   => 'string',
        self::first_rate_adjustment_date                  => 'string',
        self::first_payment_adjustment_date               => 'string',
        self::arm_margin                                  => 'string',
        self::lifetime_rate_cap                           => 'string',
        self::lifetime_rate_floor                         => 'string',
        self::periodic_rate_increase_limit                => 'string',
        self::periodic_rate_decrease_limit                => 'string',
        self::periodic_pay_adjustment_max                 => 'string',
        self::payment_frequency                           => 'string',
        self::rate_reset_frequency                        => 'string',
        self::pay_reset_frequency                         => 'string',
        self::rounding_code                               => 'string',
        self::rounding_increment                          => 'string',
        self::index_look_back_in_days                     => 'string',
        self::negative_amortization_allowed_y_n           => 'string',
        self::max_neg_allowed_of_orig_bal                 => 'string',
        self::maximum_negate_allowed                      => 'string',
        self::remaining_term_at_contribution              => 'string',
        self::remaining_amort_term_at_contribution        => 'string',
        self::maturity_date_at_contribution               => 'string',
        self::scheduled_principal_balance_at_contribution => 'string',
        self::note_rate_at_contribution                   => 'string',
        self::servicer_and_trustee_fee_rate               => 'string',
        self::fee_rate_strip_rate_1                       => 'string',
        self::fee_rate_strip_rate_2                       => 'string',
        self::fee_rate_strip_rate_3                       => 'string',
        self::fee_rate_strip_rate_4                       => 'string',
        self::fee_rate_strip_rate_5                       => 'string',
        self::net_rate_at_contribution                    => 'string',
        self::periodic_p_i_payment_at_contribution        => 'string',
        self::number_of_properties_at_contribution        => 'string',
        self::property_name                               => 'string',
        self::property_address                            => 'string',
        self::property_city                               => 'string',
        self::property_state                              => 'string',
        self::property_zip_code                           => 'string',
        self::property_county                             => 'string',
        self::property_type                               => 'string',
        self::net_rentable_square_feet_at_contribution    => 'string',
        self::number_of_units_beds_rooms_at_contribution  => 'string',
        self::year_built                                  => 'string',
        self::noi_at_contribution                         => 'string',
        self::dscr_noi_at_contribution                    => 'string',
        self::valuation_amount_at_contribution            => 'string',
        self::valuation_date_at_contribution              => 'string',
        self::physical_occupancy_at_contribution          => 'string',
        self::revenue_at_contribution                     => 'string',
        self::operating_expenses_at_contribution          => 'string',
        self::contribution_financials_as_of_date          => 'string',
        self::recourse_y_n                                => 'string',
        self::empty_field_fka_ground_lease_y_s_n          => 'string',
        self::cross_collateralized_loan_grouping          => 'string',
        self::collection_of_escrow_y_n                    => 'string',
        self::collection_of_other_reserves_y_n            => 'string',
        self::lien_position_at_contribution               => 'string',
        self::current_hyper_amortizing_date               => 'string',
        self::defeasance_option_start_date                => 'string',
        self::empty_field_fka_defeasance_option_end_date  => 'string',
        self::last_setup_change_date                      => 'string',
        self::ncf_at_contribution                         => 'string',
        self::dscr_ncf_at_contribution                    => 'string',
        self::dscr_indicator_at_contribution              => 'string',
        self::loan_contributor_to_securitization          => 'string',
        self::credit_tenant_lease_y_n                     => 'string',
        self::financial_information_submission_penalties  => 'string',
        self::additional_financing_indicator              => 'string',
        self::loan_structure                              => 'string',
        self::origination_date                            => 'string',
        self::original_interest_only_term                 => 'string',
        self::underwriting_indicator                      => 'string',
        self::servicing_advance_methodology               => 'string',
        self::valuation_source_at_contribution            => 'string',
    ];

    protected $fillable = [
        self::shelf,
        self::series,
        self::transaction_id,
        self::group_id,
        self::loan_id,
        self::prospectus_loan_id,
        self::original_note_amount,
        self::original_term_of_loan,
        self::original_amortization_term,
        self::original_note_rate,
        self::original_payment_rate,
        self::first_loan_payment_due_date,
        self::grace_days_allowed,
        self::interest_only_y_n,
        self::balloon_y_n,
        self::interest_rate_type,
        self::interest_accrual_method,
        self::interest_in_arrears_y_n,
        self::payment_type,
        self::prepayment_lock_out_end_date,
        self::yield_maintenance_end_date,
        self::prepayment_premium_end_date,
        self::prepayment_terms_description,
        self::arm_index,
        self::first_rate_adjustment_date,
        self::first_payment_adjustment_date,
        self::arm_margin,
        self::lifetime_rate_cap,
        self::lifetime_rate_floor,
        self::periodic_rate_increase_limit,
        self::periodic_rate_decrease_limit,
        self::periodic_pay_adjustment_max,
        self::payment_frequency,
        self::rate_reset_frequency,
        self::pay_reset_frequency,
        self::rounding_code,
        self::rounding_increment,
        self::index_look_back_in_days,
        self::negative_amortization_allowed_y_n,
        self::max_neg_allowed_of_orig_bal,
        self::maximum_negate_allowed,
        self::remaining_term_at_contribution,
        self::remaining_amort_term_at_contribution,
        self::maturity_date_at_contribution,
        self::scheduled_principal_balance_at_contribution,
        self::note_rate_at_contribution,
        self::servicer_and_trustee_fee_rate,
        self::fee_rate_strip_rate_1,
        self::fee_rate_strip_rate_2,
        self::fee_rate_strip_rate_3,
        self::fee_rate_strip_rate_4,
        self::fee_rate_strip_rate_5,
        self::net_rate_at_contribution,
        self::periodic_p_i_payment_at_contribution,
        self::number_of_properties_at_contribution,
        self::property_name,
        self::property_address,
        self::property_city,
        self::property_state,
        self::property_zip_code,
        self::property_county,
        self::property_type,
        self::net_rentable_square_feet_at_contribution,
        self::number_of_units_beds_rooms_at_contribution,
        self::year_built,
        self::noi_at_contribution,
        self::dscr_noi_at_contribution,
        self::valuation_amount_at_contribution,
        self::valuation_date_at_contribution,
        self::physical_occupancy_at_contribution,
        self::revenue_at_contribution,
        self::operating_expenses_at_contribution,
        self::contribution_financials_as_of_date,
        self::recourse_y_n,
        self::empty_field_fka_ground_lease_y_s_n,
        self::cross_collateralized_loan_grouping,
        self::collection_of_escrow_y_n,
        self::collection_of_other_reserves_y_n,
        self::lien_position_at_contribution,
        self::current_hyper_amortizing_date,
        self::defeasance_option_start_date,
        self::empty_field_fka_defeasance_option_end_date,
        self::last_setup_change_date,
        self::ncf_at_contribution,
        self::dscr_ncf_at_contribution,
        self::dscr_indicator_at_contribution,
        self::loan_contributor_to_securitization,
        self::credit_tenant_lease_y_n,
        self::financial_information_submission_penalties,
        self::additional_financing_indicator,
        self::loan_structure,
        self::origination_date,
        self::original_interest_only_term,
        self::underwriting_indicator,
        self::servicing_advance_methodology,
        self::valuation_source_at_contribution,
    ];


    public function __construct( array $attributes = [] ) {
        parent::__construct( $attributes );
        $this->connection = env( 'DB_CONNECTION_CUSTODIAN_CTS' );
    }
}