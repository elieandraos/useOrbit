export interface PolicyMedicalDetails {
    id: number;
    coverage_scope: string;
    coverage_scope_label: string;
    class_tier: string;
    class_tier_label: string;
    co_insurance: boolean;
    co_insurance_share: string | null;
    guaranteed_renewable: boolean;
    insured_full_name: string | null;
    insured_date_of_birth: string | null;
    insured_date_of_birth_formatted: string | null;
    insured_gender: string | null;
    insured_gender_label: string | null;
    insured_smoker: boolean | null;
    insured_medical_history: string | null;
}

export interface PolicyInsured {
    id: number;
    member_code: string;
    full_name: string;
    relationship: string;
    date_of_birth: string;
    date_of_birth_formatted: string;
    gender: string | null;
    gender_label: string | null;
    medical_notes: string | null;
    status: string;
}

export interface PolicyMedicalResource {
    id: number;
    slug: string;
    policy_number: string;
    class: string;
    class_label: string;
    subclass: string;
    type: string;
    type_label: string;
    client: {
        id: number;
        slug: string;
        full_name: string;
    };
    carrier: {
        id: number;
        slug: string;
        name: string;
    };
    agent: {
        id: number;
        slug: string;
        full_name: string;
    } | null;
    effective_date: string;
    effective_date_formatted: string;
    expiry_date: string;
    expiry_date_formatted: string;
    premium_amount: string;
    discount_amount: string;
    net_premium: string;
    status: string;
    status_label: string;
    source: string;
    source_label: string;
    details: PolicyMedicalDetails;
    insureds: PolicyInsured[];
}
