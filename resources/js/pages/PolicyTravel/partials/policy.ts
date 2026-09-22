export interface PolicyTravelDetails {
    id: number;
    destination: string;
    trip_start_date: string;
    trip_start_date_formatted: string;
    trip_end_date: string;
    trip_end_date_formatted: string;
    travelers: string;
    coverage_tier: string;
}

export interface PolicyTravelResource {
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
    details: PolicyTravelDetails;
}
