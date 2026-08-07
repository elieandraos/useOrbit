export interface OrganizationMemberResource {
    id: number;
    name: string | null;
    email: string;
    role: 'owner' | 'admin' | 'member';
    status: 'active' | 'invited' | 'suspended';
    joined_at: string | null;
    is_you: boolean;
}

export interface InvitableRoleOption {
    label: string;
    value: string;
}
