export interface CarrierBranchResource {
    id: number;
    street: string | null;
    building_floor: string | null;
    city: string | null;
    state_id: number | null;
    country_id: number | null;
    state_name: string | null;
    country_name: string | null;
    phone: string | null;
    contact_name: string;
    contact_role: string | null;
    contact_email: string | null;
    contact_phone: string | null;
    contact_department: string | null;
}

export interface CarrierResource {
    id: number;
    slug: string;
    name: string;
    phone: string | null;
    website: string | null;
    onboarded_date: string;
    onboarded_date_formatted: string;
    status: string;
    created_by: number;
    updated_by: number | null;
    created_at: string;
    updated_at: string;
    updated_by_name: string | null;
    branch: CarrierBranchResource | null;
}
