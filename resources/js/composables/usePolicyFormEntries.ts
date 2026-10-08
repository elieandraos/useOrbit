import { router, useRemember } from '@inertiajs/vue3';
import { reactive } from 'vue';
import { initialPolicyCurrencyId } from '@/composables/usePolicyCurrency';
import {
    applyPolicyDiscardRules,
    checkPolicyCreateSnapshot,
    clonePolicyEntries,
    stampPolicyCreateFlow,
    startPolicyCreateFlow,
    takePolicyCarriedWork,
} from '@/lib/policyCreateFlow';
import type {
    PolicyCarriedWork,
    PolicyCommonEntries,
    PolicyCreateFlowStamp,
} from '@/lib/policyCreateFlow';
import type { PolicyEntry, PolicyParties } from '@/types/policy';

export interface PolicyFormEntries<TDetails extends object> {
    common: PolicyCommonEntries;
    /** The class's own entries: subclass and class sections. */
    details: TDetails;
}

type RememberedPolicyFormEntries<TDetails extends object> =
    PolicyFormEntries<TDetails> & PolicyCreateFlowStamp;

export type UsePolicyFormEntriesReturn<TDetails extends object> = {
    entries: PolicyFormEntries<TDetails>;
    /** This page's entries as work to carry to the other step. */
    carriedWork: () => PolicyCarriedWork;
};

/**
 * The common fields a class form starts with: the edited policy's, or empty with the organization's currency.
 */
export function initialPolicyCommonEntries(
    policy:
        | (Pick<PolicyParties, 'carrier_branch'> & {
              policy_number: string | null;
              effective_date: string;
              expiry_date: string;
              currency_id: number;
              premium_amount: string;
              discount_amount: string | null;
          })
        | undefined,
    defaultCurrencyId: number | null | undefined,
): PolicyCommonEntries {
    return {
        policy_number: policy?.policy_number ?? '',
        carrier_branch_id: `${policy?.carrier_branch?.id ?? ''}`,
        effective_date: policy?.effective_date ?? '',
        expiry_date: policy?.expiry_date ?? '',
        currency_id: initialPolicyCurrencyId(
            policy?.currency_id,
            defaultCurrencyId,
        ),
        premium_amount: policy?.premium_amount ?? '',
        discount_amount: policy?.discount_amount ?? '',
    };
}

/**
 * A class form's entries. On Edit they're seeded from the policy and nothing is remembered or carried. On a new
 * policy they're remembered in the history entry under a class key, stamped with the Create flow's record, and come
 * from (in order): this entry's own snapshot when restored with the discards made since applied; work handed over
 * by the previous step with the discard rules applied; or the defaults, starting a new flow.
 */
export function usePolicyFormEntries<TDetails extends object>(options: {
    policyClass: string;
    entry: PolicyEntry | undefined;
    common: PolicyCommonEntries;
    details: () => TDetails;
}): UsePolicyFormEntriesReturn<TDetails> {
    const { policyClass, entry } = options;

    if (!entry) {
        return {
            entries: reactive({
                common: options.common,
                details: options.details(),
            }) as PolicyFormEntries<TDetails>,
            carriedWork: () => {
                throw new Error('An edited policy carries no Create work.');
            },
        };
    }

    const carrierId = `${entry.carrier.id}`;
    const rememberKey = `Policies/Create/${policyClass}`;
    const restored = router.restore(rememberKey) as
        RememberedPolicyFormEntries<TDetails> | undefined;
    const handedOver = takePolicyCarriedWork();
    const restoreCheck = restored ? checkPolicyCreateSnapshot(restored) : null;

    let initial: RememberedPolicyFormEntries<TDetails>;

    if (restored && restoreCheck) {
        const snapshot = clonePolicyEntries(restored);

        initial = {
            ...snapshot,
            ...stampPolicyCreateFlow(),
            details: restoreCheck.dropDetails
                ? options.details()
                : snapshot.details,
            common: {
                ...snapshot.common,
                carrier_branch_id: restoreCheck.dropBranch
                    ? ''
                    : snapshot.common.carrier_branch_id,
            },
        };
    } else {
        // A visit that isn't a flow navigation starts a new flow. An older entry of another flow (or of this one
        // after a refresh) restored from history starts empty, joining the current flow rather than replacing it.
        if (!handedOver && !restored) {
            startPolicyCreateFlow();
        }

        const work = handedOver?.work
            ? applyPolicyDiscardRules(handedOver.work, policyClass, carrierId)
            : null;

        initial = {
            ...stampPolicyCreateFlow(),
            common: { ...options.common, ...work?.common },
            details: {
                ...options.details(),
                ...(work?.details as Partial<TDetails> | null),
            },
        };
    }

    // Written back first, so the restore below gets the checked, re-stamped snapshot instead of the stored one.
    router.remember(clonePolicyEntries(initial), rememberKey);

    const entries = useRemember(
        reactive(initial),
        rememberKey,
    ) as RememberedPolicyFormEntries<TDetails>;

    return {
        entries,
        carriedWork: () => ({
            class: policyClass,
            carrierId,
            common: entries.common,
            details: entries.details as Record<string, unknown>,
        }),
    };
}
