const express = require('express');
const router = express.Router();
const db = require('../db.js');
const bcrypt = require('bcrypt');

router.post('/register', async (req, res) => {
  const { nombre, correo, password } = req.body;

  if (!nombre || !correo || !password) {
    return res.status(400).json({ error: 'Todos los campos son obligatorios' });
  }

  db.query('SELECT id FROM empresas WHERE correo = ?', [correo], async (err, results) => {
    if (err) {
      console.error('Error al comprobar correo:', err);
      return res.status(500).json({ error: 'Error en el servidor' });
    }
    if (results.length > 0) {
      return res.status(400).json({ error: 'El correo ya está registrado' });
    }

    try {
      const hashedPassword = await bcrypt.hash(password, 10);
      const query = 'INSERT INTO empresas (nombre, correo, password) VALUES (?, ?, ?)';
      db.query(query, [nombre, correo, hashedPassword], (err, result) => {
        if (err) {
          console.error('Error al registrar empresa:', err);
          return res.status(500).json({ error: 'Error en el servidor' });
        }
        res.json({ message: 'Empresa registrada con éxito' });
      });
    } catch (error) {
      console.error('Error al encriptar la contraseña:', error);
      return res.status(500).json({ error: 'Error en el servidor' });
    }
  });
});


// Login de empresa
router.post('/login', (req, res) => {
  const { correo, password } = req.body;

  if (!correo || !password) {
    return res.status(400).json({ error: 'Correo y contraseña son obligatorios' });
  }

  const query = 'SELECT * FROM empresas WHERE correo = ? LIMIT 1';
  db.query(query, [correo], async (err, results) => {
    if (err) {
      console.error('Error en login:', err);
      return res.status(500).json({ error: 'Error en el servidor' });
    }

    if (results.length === 0) {
      return res.status(401).json({ error: 'Credenciales inválidas' });
    }

    const empresa = results[0];
    const match = await bcrypt.compare(password, empresa.password);

    if (!match) {
      return res.status(401).json({ error: 'Credenciales inválidas' });
    }

    res.json({
      message: 'Login exitoso',
      empresa: {
        id: empresa.id,
        nombre: empresa.nombre,
        correo: empresa.correo
      }
    });
  });
});

module.exports = router;