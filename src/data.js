// Massive database of exercises for DuckGym
export const EXERCISES_DB = {
  "fuerza": [
    { id: "s1", name: "Peso Muerto", desc: "Peso muerto convencional con barra.", equipment: "barra", category: "piernas" },
    { id: "s2", name: "Press de Banca", desc: "Press de banca con barra.", equipment: "barra", category: "empuje" },
    { id: "s3", name: "Press Militar", desc: "Press militar con mancuernas.", equipment: "mancuernas", category: "hombros" },
    { id: "s4", name: "Remo con Barra", desc: "Remo con barra para espalda.", equipment: "barra", category: "espalda" },
    { id: "s5", name: "Extensiones Tríceps", desc: "Extensión de tríceps con mancuerna.", equipment: "mancuernas", category: "brazos" },
    { id: "s6", name: "Curl de Bíceps", desc: "Curl de bíceps con mancuernas.", equipment: "mancuernas", category: "brazos" }
  ],
  "calistenia": [
    { id: "c1", name: "Flexiones de Brazo", desc: "Flexiones clásicas.", equipment: "peso corporal", category: "empuje" },
    { id: "c2", name: "Dominadas", desc: "Dominadas con agarre neutro.", equipment: "barra", category: "tracción" },
    { id: "c3", name: "Pistol Squat", desc: "Sentadilla a una sola pierna.", equipment: "peso corporal", category: "piernas" },
    { id: "c4", name: "Fondos", desc: "Fondos en paralelas.", equipment: "barra", category: "empuje" },
    { id: "c5", name: "Plancha Abdominal", desc: "Plancha isométrica.", equipment: "peso corporal", category: "core" },
    { id: "c6", name: "Muscle-up", desc: "Transición de dominada.", equipment: "barra", category: "tracción" },
    { id: "c7", name: "Flexiones Diamante", desc: "Flexiones manos juntas.", equipment: "peso corporal", category: "empuje" },
    { id: "c8", name: "L-Sit", desc: "Elevación de piernas en L colgado o suelo.", equipment: "peso corporal", category: "core" }
  ],
  "hiit": [
    { id: "h1", name: "Burpees", desc: "Burpee completo con flexión.", equipment: "peso corporal", category: "cardio" },
    { id: "h2", name: "Mountain Climbers", desc: "Escaladores en plan.", equipment: "peso corporal", category: "core" },
    { id: "h3", name: "Jumping Jacks", desc: "Saltos de apertura clásicos.", equipment: "peso corporal", category: "cardio" },
    { id: "h4", name: "Skaters", desc: "Saltos laterales de patinador.", equipment: "peso corporal", category: "cardio" },
    { id: "h5", name: "High Knees", desc: "Correr rodillas altas.", equipment: "peso corporal", category: "cardio" },
    { id: "h6", name: "Squat Jumps", desc: "Sentadillas con salto.", equipment: "peso corporal", category: "piernas" }
  ],
  "yoga": [
    { id: "y1", name: "Saludo al Sol", desc: "Secuencia completa de saludo.", equipment: "peso corporal", category: "movilidad" },
    { id: "y2", name: "Perro mirando abajo", desc: "Estiramiento de cadena posterior.", equipment: "peso corporal", category: "movilidad" },
    { id: "y3", name: "Guerrero I", desc: "Postura de guerrero.", equipment: "peso corporal", category: "equilibrio" },
    { id: "y4", name: "Postura del Niño", desc: "Descanso en niño.", equipment: "peso corporal", category: "relajación" },
    { id: "y5", name: "Cobra", desc: "Extensión suave de espalda.", equipment: "peso corporal", category: "extensión" },
    { id: "y6", name: "Árbol", desc: "Equilibrio sobre una pierna.", equipment: "peso corporal", category: "equilibrio" }
  ]
};
