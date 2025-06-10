const express = require('express');
const router = express.Router();
const db = require('../db.js');
const bcrypt = require('bcrypt');

// Registro de usuario
router.post('/register', async (req, res) => {
  const { username, email, password } = req.body;
  if (!username || !email || !password)
    return res.status(400).json({ error: 'Faltan campos obligatorios' });

  // Comprobar si el correo ya está registrado
  db.query('SELECT id FROM users WHERE email = ?', [email], async (err, results) => {
    if (err) return res.status(500).json({ error: 'Error de servidor' });
    if (results.length > 0) {
      return res.status(400).json({ error: 'El correo ya está registrado' });
    }

    try {
      const hash = await bcrypt.hash(password, 10);
      db.query('INSERT INTO users (username, email, password) VALUES (?, ?, ?)', [username, email, hash], (err) => {
        if (err) return res.status(500).json({ error: 'Error al registrar usuario' });
        res.json({ message: 'Usuario registrado con éxito' });
      });
    } catch (err) {
      res.status(500).json({ error: 'Error de servidor' });
    }
  });
});

// Login seguro
router.post('/login', (req, res) => {
  const { email, password } = req.body;
  db.query('SELECT * FROM users WHERE email = ?', [email], async (err, results) => {
    if (err) return res.status(500).json({ error: 'Error en login' });
    if (results.length === 0) return res.status(401).json({ error: 'Credenciales inválidas' });

    const user = results[0];
    const match = await bcrypt.compare(password, user.password);
    if (!match) return res.status(401).json({ error: 'Credenciales inválidas' });

    // No devuelvas el hash de la contraseña
    delete user.password;
    res.json({ message: 'Login exitoso', user });
  });
});

module.exports = router;