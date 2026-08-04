export interface AgentResource {
    id: number;
    slug: string;
    first_name: string;
    last_name: string;
    full_name: string;
    date_of_birth: string;
    date_of_birth_formatted: string;
    age: number;
    joined_at: string;
    joined_at_formatted: string;
    tenure: string;
    phone: string;
    email: string;
    street: string | null;
    building_floor: string | null;
    country_id: number | null;
    state_id: number | null;
    city: string | null;
    country_name: string | null;
    state_name: string | null;
    full_address: string;
    status: string;
    created_by: number;
    updated_by: number | null;
    created_at: string;
    updated_at: string;
    updated_by_name: string | null;
}
