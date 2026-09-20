export interface PolicyFireDetails {
    id: number;
    property_type: string;
    floor_area: number;
    year_built: number | null;
    street: string;
    building_floor: string | null;
    city: string;
    state_id: number | null;
    state_name: string | null;
    country_id: number | null;
    country_name: string | null;
    sum_insured: string;
}

export interface PolicyFireResource {
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
    details: PolicyFireDetails;
}
