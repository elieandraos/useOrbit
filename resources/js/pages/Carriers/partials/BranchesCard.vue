<script setup lang="ts">
import { ExternalLink, MapPin, Pencil, Plus, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Avatar } from '@/components/ui/avatar';
import Badge from '@/components/ui/badge/Badge.vue';
import Button from '@/components/ui/button/Button.vue';
import {
    Card,
    CardAction,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import BranchModal from './BranchModal.vue';
import type { CarrierBranchResource, CarrierResource } from './carrier';
import DeleteBranchModal from './DeleteBranchModal.vue';

const props = defineProps<{
    carrier: CarrierResource;
    countries: { id: number; name: string }[];
}>();

const showBranchModal = ref(false);
const branchBeingEdited = ref<CarrierBranchResource | null>(null);
const branchToDelete = ref<CarrierBranchResource | null>(null);

function openAddModal() {
    branchBeingEdited.value = null;
    showBranchModal.value = true;
}

function openEditModal(branch: CarrierBranchResource) {
    branchBeingEdited.value = branch;
    showBranchModal.value = true;
}

function addressLines(branch: CarrierBranchResource) {
    return [
        branch.building_floor,
        branch.street,
        branch.city,
        branch.state_name,
        branch.country_name,
    ].filter((line): line is string => !!line);
}

function mapsUrl(branch: CarrierBranchResource) {
    return (
        'https://www.google.com/maps/search/?api=1&query=' +
        encodeURIComponent(addressLines(branch).join(', '))
    );
}

const branches = computed(() => props.carrier.branches);
</script>

<template>
    <Card>
        <CardHeader bordered>
            <CardTitle>Branches</CardTitle>
            <Badge v-if="branches.length > 0" tone="accent">{{
                branches.length
            }}</Badge>
            <CardAction>
                <Button variant="ghost" size="sm" @click="openAddModal">
                    <template #leading><Plus /></template>
                    Add branch
                </Button>
            </CardAction>
        </CardHeader>
        <CardContent v-if="branches.length" class="p-0">
            <div
                v-for="branch in branches"
                :key="branch.id"
                class="flex items-start gap-3.5 border-b border-border-subtle p-[18px] last:border-b-0"
            >
                <div
                    class="flex size-9 shrink-0 items-center justify-center rounded-md bg-accent-bg text-accent"
                >
                    <MapPin class="size-4" />
                </div>
                <div class="min-w-0 flex-1">
                    <span
                        v-if="branch.city"
                        class="text-[13.5px] font-semibold text-primary"
                        >{{ branch.city }}</span
                    >
                    <div
                        class="mt-1 text-[12.5px] leading-[1.55] text-secondary"
                    >
                        <div v-for="line in addressLines(branch)" :key="line">
                            {{ line }}
                        </div>
                    </div>
                    <a
                        :href="mapsUrl(branch)"
                        target="_blank"
                        rel="noreferrer"
                        class="mt-2 inline-flex items-center gap-1 text-[11.5px] font-medium text-accent"
                    >
                        <ExternalLink class="size-3" />
                        Open in Google Maps
                    </a>

                    <div
                        class="mt-2.5 flex items-center gap-2.5 border-t border-border-subtle pt-2.5"
                    >
                        <Avatar :name="branch.contact_name" :size="26" />
                        <div class="min-w-0">
                            <div
                                class="flex flex-wrap items-center gap-1.5 text-[12.5px] font-medium text-primary"
                            >
                                {{ branch.contact_name }}
                            </div>
                            <div
                                class="mt-0.5 flex flex-wrap gap-3 font-mono text-[11.5px] text-tertiary"
                            >
                                <span v-if="branch.contact_email">{{
                                    branch.contact_email
                                }}</span>
                                <span v-if="branch.contact_phone">{{
                                    branch.contact_phone
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex shrink-0 gap-1">
                    <button
                        type="button"
                        title="Edit branch"
                        class="inline-flex size-7 cursor-pointer items-center justify-center rounded-md text-secondary transition-colors hover:bg-sunken"
                        @click="openEditModal(branch)"
                    >
                        <Pencil class="size-3.5" />
                    </button>
                    <button
                        type="button"
                        title="Delete branch"
                        class="inline-flex size-7 cursor-pointer items-center justify-center rounded-md text-secondary transition-colors hover:bg-sunken hover:text-danger"
                        @click="branchToDelete = branch"
                    >
                        <Trash2 class="size-3.5" />
                    </button>
                </div>
            </div>
        </CardContent>
        <CardContent v-else class="text-center text-[13px] text-tertiary"
            >No branch on file.</CardContent
        >

        <BranchModal
            v-model:open="showBranchModal"
            :carrier="carrier"
            :countries="countries"
            :branch="branchBeingEdited"
        />
        <DeleteBranchModal v-model="branchToDelete" />
    </Card>
</template>
