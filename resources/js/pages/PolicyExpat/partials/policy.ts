export interface PolicyExpatDetails {
    id: number;
    coverage_zone: string;
    coverage_zone_label: string;
    travel_scope: string | null;
    full_name: string;
    gender: string;
    gender_label: string;
    nationality: string;
    date_of_birth: string;
    date_of_birth_formatted: string;
    phone: string;
    country_id: number | null;
    country_name: string | null;
    visa_expiry_date: string | null;
    visa_expiry_date_formatted: string | null;
}

export interface PolicyExpatResource {
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
    details: PolicyExpatDetails;
}
