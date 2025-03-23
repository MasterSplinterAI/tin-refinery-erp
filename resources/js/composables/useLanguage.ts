import { ref, computed } from 'vue';

const translations = {
  en: {
    batchNumber: 'Batch Number',
    date: 'Date',
    processes: 'Processes',
    process: 'Process',
    status: 'Status',
    actions: 'Actions',
    edit: 'Edit',
    delete: 'Delete',
    cancel: 'Cancel',
    completed: 'Completed',
    inProgress: 'In Progress',
    canceled: 'Canceled',
    deleteBatch: 'Delete Batch',
    confirmDelete: 'Are you sure you want to delete batch',
    refiningStep: 'Refining Step',
    removeStep: 'Remove Step',
    inputTin: 'Input Tin',
    inputSlag: 'Input Slag',
    outputTin: 'Output Tin',
    slag: 'Slag',
    kilos: 'Kilos',
    snContent: 'Sn Content',
    notes: 'Notes',
    save: 'Save',
    addProcess: 'Add Process',
    batchManagement: 'Batch Management',
    newBatch: 'New Batch',
  },
  es: {
    batchNumber: 'Número de Lote',
    date: 'Fecha',
    processes: 'Procesos',
    process: 'Proceso',
    status: 'Estado',
    actions: 'Acciones',
    edit: 'Editar',
    delete: 'Eliminar',
    cancel: 'Cancelar',
    completed: 'Completado',
    inProgress: 'En Progreso',
    canceled: 'Cancelado',
    deleteBatch: 'Eliminar Lote',
    confirmDelete: '¿Está seguro que desea eliminar el lote',
    refiningStep: 'Paso de Refinación',
    removeStep: 'Eliminar Paso',
    inputTin: 'Estaño de Entrada',
    inputSlag: 'Escoria de Entrada',
    outputTin: 'Estaño de Salida',
    slag: 'Escoria',
    kilos: 'Kilos',
    snContent: 'Contenido Sn',
    notes: 'Notas',
    save: 'Guardar',
    addProcess: 'Agregar Proceso',
    batchManagement: 'Gestión de Lotes',
    newBatch: 'Nuevo Lote',
  }
};

const currentLanguage = ref('en');

export function useLanguage() {
  const setLanguage = (lang: 'en' | 'es') => {
    currentLanguage.value = lang;
  };

  const t = (key: keyof typeof translations.en) => {
    return translations[currentLanguage.value][key] || key;
  };

  const language = computed(() => currentLanguage.value);

  return {
    t,
    setLanguage,
    language,
  };
} 