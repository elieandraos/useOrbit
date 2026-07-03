export interface ClientResource {
    id: number;
    slug: string;
    first_name: string;
    middle_name: string | null;
    last_name: string;
    full_name: string;
    mothers_name: string | null;
    date_of_birth_formatted: string;
    age: number;
    gender_label: string;
    photo: string | null;
    phone: string;
    email: string | null;
    full_address: string;
    emergency_contact_name: string | null;
    emergency_contact_relationship_label: string | null;
    emergency_contact_phone: string | null;
    enrollment_date_formatted: string;
    lead_source_label: string;
    status: string;
}
