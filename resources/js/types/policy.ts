export type PolicyClass =
    'medical' | 'automotive' | 'expat' | 'fire' | 'life' | 'travel';

export interface PolicyResource {
    id: number;
    slug: string;
    policy_number: string;
    class: PolicyClass;
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
    carrier_branch?: {
        id: number;
        label: string;
    } | null;
    agent: {
        id: number;
        slug: string;
        full_name: string;
    } | null;
    currency_id: number;
    currency_code: string;
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
}

export interface PolicyCurrencyOption {
    id: number;
    code: string;
    name: string;
}

export interface PolicyPartyOption {
    id: number;
    full_name?: string;
    name?: string;
}

export type PolicyParties = Pick<
    PolicyResource,
    'client' | 'carrier' | 'agent'
>;
