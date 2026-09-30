import {
    edit as automotiveEdit,
    exportPdf as automotiveExportPdf,
    show as automotiveShow,
} from '@/routes/policies/automotive';
import {
    edit as expatEdit,
    exportPdf as expatExportPdf,
    show as expatShow,
} from '@/routes/policies/expat';
import {
    edit as fireEdit,
    exportPdf as fireExportPdf,
    show as fireShow,
} from '@/routes/policies/fire';
import {
    edit as lifeEdit,
    exportPdf as lifeExportPdf,
    show as lifeShow,
} from '@/routes/policies/life';
import {
    edit as medicalEdit,
    exportPdf as medicalExportPdf,
    show as medicalShow,
} from '@/routes/policies/medical';
import {
    edit as travelEdit,
    exportPdf as travelExportPdf,
    show as travelShow,
} from '@/routes/policies/travel';
import type { PolicyClass } from '@/types/policy';

type PolicyRoute = (slug: string) => { url: string };

export const policyClassRoutes: Record<
    PolicyClass,
    { show: PolicyRoute; edit: PolicyRoute; exportPdf: PolicyRoute }
> = {
    medical: {
        show: medicalShow,
        edit: medicalEdit,
        exportPdf: medicalExportPdf,
    },
    automotive: {
        show: automotiveShow,
        edit: automotiveEdit,
        exportPdf: automotiveExportPdf,
    },
    expat: { show: expatShow, edit: expatEdit, exportPdf: expatExportPdf },
    fire: { show: fireShow, edit: fireEdit, exportPdf: fireExportPdf },
    life: { show: lifeShow, edit: lifeEdit, exportPdf: lifeExportPdf },
    travel: { show: travelShow, edit: travelEdit, exportPdf: travelExportPdf },
};
