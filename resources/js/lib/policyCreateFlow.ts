import type { Page, VisitOptions } from '@inertiajs/core';
import { router } from '@inertiajs/vue3';

/**
 * The step-2 fields every policy class has. A class change keeps them; a carrier change drops only the branch.
 */
export interface PolicyCommonEntries {
    policy_number: string;
    carrier_branch_id: string;
    effective_date: string;
    expiry_date: string;
    currency_id: string;
    premium_amount: string;
    discount_amount: string;
}

/**
 * Step-2 entries carried between the Create steps, with the class and carrier they belong to.
 */
export interface PolicyCarriedWork {
    class: string;
    carrierId: string;
    common: PolicyCommonEntries;
    /** The class's own entries (subclass and class sections); null once a class change discarded them. */
    details: Record<string, unknown> | null;
}

/**
 * The current flow's record, also stamped on every remembered snapshot of the flow's pages. Each count only ever
 * grows by one per discard, so a snapshot stamped with a lower count was written before a discard it must undergo.
 */
export interface PolicyCreateFlowStamp {
    flowId: string;
    classDiscards: number;
    branchDiscards: number;
}

let currentFlow: PolicyCreateFlowStamp | null = null;

/** Work handed from the leaving page to the arriving one; null work still continues the flow. */
let handOver: { flowId: string; work: PolicyCarriedWork | null } | null = null;

const createFlowComponent = /^(Policies|Policy[A-Za-z]+)\/Create$/;

/**
 * A plain copy of form entries, which may be reactive proxies that `structuredClone` rejects.
 */
export function clonePolicyEntries<T>(entries: T): T {
    return JSON.parse(JSON.stringify(entries)) as T;
}

function newFlowId(): string {
    return (
        globalThis.crypto?.randomUUID?.() ?? `${Date.now()}-${Math.random()}`
    );
}

/**
 * Start a new flow, replacing the record so entries of any earlier flow in the tab restore without their work.
 */
export function startPolicyCreateFlow(): PolicyCreateFlowStamp {
    currentFlow = { flowId: newFlowId(), classDiscards: 0, branchDiscards: 0 };

    return { ...currentFlow };
}

/**
 * The current flow's counts, to stamp a snapshot with.
 */
export function stampPolicyCreateFlow(): PolicyCreateFlowStamp {
    return currentFlow ? { ...currentFlow } : startPolicyCreateFlow();
}

/**
 * Check a page's restored snapshot against the flow record: null when it belongs to another flow (or none is in
 * memory, e.g. after a refresh); otherwise which of its discardable entries were discarded since it was written.
 */
export function checkPolicyCreateSnapshot(
    stamp: Partial<PolicyCreateFlowStamp> | undefined,
): { dropDetails: boolean; dropBranch: boolean } | null {
    if (!currentFlow || !stamp || stamp.flowId !== currentFlow.flowId) {
        return null;
    }

    return {
        dropDetails: (stamp.classDiscards ?? 0) < currentFlow.classDiscards,
        dropBranch: (stamp.branchDiscards ?? 0) < currentFlow.branchDiscards,
    };
}

/**
 * Remove from a restored snapshot's work the entries discarded since it was written.
 */
export function dropDiscardedPolicyWork(
    work: PolicyCarriedWork,
    check: { dropDetails: boolean; dropBranch: boolean },
): PolicyCarriedWork {
    return {
        ...work,
        details: check.dropDetails ? null : work.details,
        common: {
            ...work.common,
            carrier_branch_id: check.dropBranch
                ? ''
                : work.common.carrier_branch_id,
        },
    };
}

/**
 * Apply the discard rules for the selected class and carrier: a different class drops the class's own entries, a
 * different carrier drops the issuing branch. Both remove the values and count the discard; nothing is hidden.
 */
export function applyPolicyDiscardRules(
    work: PolicyCarriedWork,
    policyClass: string,
    carrierId: string,
): PolicyCarriedWork {
    const kept: PolicyCarriedWork = clonePolicyEntries(work);
    const flow = currentFlow ?? startPolicyCreateFlow();

    if (policyClass !== '' && kept.class !== policyClass) {
        kept.class = policyClass;
        kept.details = null;
        flow.classDiscards += 1;
    }

    if (carrierId !== '' && kept.carrierId !== carrierId) {
        kept.carrierId = carrierId;
        kept.common.carrier_branch_id = '';
        flow.branchDiscards += 1;
    }

    currentFlow = flow;

    return kept;
}

/**
 * Take the work handed over by the previous page of this flow, once. Returns null when the page wasn't reached
 * through a flow navigation; a result with null work still continues the current flow.
 */
export function takePolicyCarriedWork(): {
    work: PolicyCarriedWork | null;
} | null {
    const taken = handOver;
    handOver = null;

    if (!taken || !currentFlow || taken.flowId !== currentFlow.flowId) {
        return null;
    }

    return { work: taken.work ? clonePolicyEntries(taken.work) : null };
}

/**
 * Visit options for a flow navigation (Continue, Back, the correction link): the work is handed over right before
 * the visit and left for the arriving Create page to take. If the visit ends anywhere else, it's emptied.
 */
export function carryPolicyWork(
    work: () => PolicyCarriedWork | null,
): Pick<VisitOptions, 'onBefore' | 'onSuccess' | 'onFinish'> {
    let reachedCreatePage = false;

    return {
        onBefore: () => {
            reachedCreatePage = false;

            const flow = stampPolicyCreateFlow();
            const carried = work();

            handOver = {
                flowId: flow.flowId,
                work: carried ? clonePolicyEntries(carried) : null,
            };
        },
        onSuccess: (page: Page) => {
            reachedCreatePage = createFlowComponent.test(page.component);
        },
        onFinish: () => {
            if (!reachedCreatePage) {
                handOver = null;
            }
        },
    };
}

/**
 * Forget the flow in memory: its record and any hand-over. A successful create calls this once the server has
 * cleared history.
 */
export function forgetPolicyCreateFlow(): void {
    currentFlow = null;
    handOver = null;
}

/**
 * End the policy Create flow: forget its work and record, and, as its pages' history is encrypted, forget the key
 * so browser Back, Forward or a bfcache restore re-fetch them fresh, without anything entered in them.
 */
export function endPolicyCreateFlow(): void {
    forgetPolicyCreateFlow();
    router.clearHistory();
}
