const express = require('express');
const router = express.Router();
const db = require('../db');

// Crear proyecto
router.post('/create', (req, res) => {
  const { user_id, name } = req.body;
  db.query('INSERT INTO projects (user_id, name) VALUES (?, ?)', [user_id, name], (err) => {
    if (err) return res.status(500).json({ error: 'Error al crear proyecto' });
    res.json({ message: 'Proyecto creado con éxito' });
  });
});

// Obtener proyectos creados por un usuario
router.get('/user/:user_id', (req, res) => {
  const userId = req.params.user_id;
  db.query('SELECT * FROM projects WHERE user_id = ?', [userId], (err, results) => {
    if (err) return res.status(500).json({ error: 'Error al obtener proyectos' });
    res.json(results);
  });
});

// Obtener proyectos donde el usuario ha postulado y la empresa ha aceptado
router.get('/assigned/:user_id', (req, res) => {
  const userId = req.params.user_id;
  const query = `
    SELECT 
      o.id AS oferta_id,
      o.nombre AS proyecto_nombre,
      o.fecha_limite,
      e.nombre AS empresa_nombre,
      e.id AS empresa_id, -- <--- ¡AÑADE ESTA LÍNEA!
      p.estado AS postulacion_estado,
      p.fecha_postulacion
    FROM postulaciones p
    JOIN ofertas o ON p.oferta_id = o.id
    JOIN empresas e ON o.empresa_id = e.id
    WHERE p.user_id = ? AND p.estado = 'aceptado'
  `;

  db.query(query, [userId], (err, results) => {
    if (err) return res.status(500).json({ error: 'Error al obtener proyectos asignados' });
    res.json(results);
  });
});

module.exports = router;
