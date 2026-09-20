export interface PolicyLifeDetails {
    id: number;
    sum_assured: string;
    term_years: number;
    smoker: boolean;
    beneficiaries: string;
}

export interface PolicyLifeResource {
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
    details: PolicyLifeDetails;
}
