const express = require('express');
const router = express.Router();
const db = require('../db');

// Obtener tareas por oferta
router.get('/oferta/:oferta_id', (req, res) => {
  const ofertaId = req.params.oferta_id;
  db.query('SELECT * FROM tasks WHERE oferta_id = ?', [ofertaId], (err, results) => {
    if (err) return res.status(500).json({ error: 'Error al obtener tareas' });
    res.json(results);
  });
});

// Crear tarea
router.post('/create', (req, res) => {
  const { oferta_id, title, description, color, due_date } = req.body;

  if (!oferta_id || !title) {
    return res.status(400).json({ error: 'Faltan datos requeridos' });
  }

  db.query(
    'INSERT INTO tasks (oferta_id, title, description, due_date, color) VALUES (?, ?, ?, ?, ?)',
    [oferta_id, title, description, due_date, color || '#007bff'],
    (err, result) => {
      if (err) return res.status(500).json({ error: 'Error al crear tarea' });
      res.json({ message: 'Tarea creada', taskId: result.insertId });
    }
  );
});

// Actualizar estado o contenido de tarea
router.put('/update/:id', (req, res) => {
  const taskId = req.params.id;
  const { title, description, status, color, due_date } = req.body;

  db.query(
    'UPDATE tasks SET title = ?, description = ?, status = ?, due_date = ?, color = ? WHERE id = ?',
    [title, description, status, due_date, color || '#007bff', taskId],
    (err, result) => {
      if (err) return res.status(500).json({ error: 'Error al actualizar tarea' });
      if (result.affectedRows === 0) return res.status(404).json({ error: 'Tarea no encontrada' });
      res.json({ message: 'Tarea actualizada' });
    }
  );
});

// Eliminar tarea
router.delete('/delete/:id', (req, res) => {
  const taskId = req.params.id;
  db.query('DELETE FROM tasks WHERE id = ?', [taskId], (err, result) => {
    if (err) return res.status(500).json({ error: 'Error al eliminar tarea' });
    if (result.affectedRows === 0) return res.status(404).json({ error: 'Tarea no encontrada' });
    res.json({ message: 'Tarea eliminada' });
  });
});

module.exports = router;