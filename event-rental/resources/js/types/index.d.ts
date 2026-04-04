export interface User {
    id: number;
    name: string;
    email: string;
    email_verified_at?: string;
    is_super_admin: boolean;
    workspace_role?: string | null;
    can_manage_workspace_users?: boolean;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: {
        user: User;
    };
};
