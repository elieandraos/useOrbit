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

export const ROLE_DESCRIPTIONS: Record<string, string> = {
    admin: 'Full access including member management and billing',
    member: "Can view and manage everything, but can't take destructive actions like archiving or deleting",
};
