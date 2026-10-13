import { useEffect, useState } from 'react'

export default function App() {
  const [members, setMembers] = useState([])
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [phone, setPhone] = useState('')
  const [status, setStatus] = useState('Checking API...')

  const fetchMembers = async () => {
    try {
      const res = await fetch('/api/members')
      if (res.ok) {
        const data = await res.json()
        setMembers(data)
      }
    } catch {
      setStatus('Gagal terhubung ke API backend')
    }
  }

  useEffect(() => {
    fetch('/api/health')
      .then((res) => res.json())
      .then((data) => setStatus(`Backend: ${data.status} | DB: ${data.database}`))
      .catch(() => setStatus('Backend offline'))

    fetchMembers()
  }, [])

  const handleSubmit = async (e) => {
    e.preventDefault()
    if (!name || !email) return

    const res = await fetch('/api/members', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name, email, phone }),
    })

    if (res.ok) {
      setName('')
      setEmail('')
      setPhone('')
      fetchMembers()
    }
  }

  return (
    <main style={{ maxWidth: 640, margin: '2rem auto', fontFamily: 'sans-serif', padding: '0 1rem' }}>
      <h1>Kelola Gym Member</h1>
      <p style={{ color: '#666' }}>{status}</p>

      <form onSubmit={handleSubmit} style={{ display: 'grid', gap: '0.5rem', marginBottom: '2rem' }}>
        <input
          placeholder="Nama Member"
          value={name}
          onChange={(e) => setName(e.target.value)}
          required
        />
        <input
          placeholder="Email Member"
          type="email"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
          required
        />
        <input
          placeholder="Nomor Telepon"
          value={phone}
          onChange={(e) => setPhone(e.target.value)}
        />
        <button type="submit" style={{ cursor: 'pointer' }}>Tambah Member</button>
      </form>

      <h2>Daftar Member ({members.length})</h2>
      <ul style={{ paddingLeft: '1.2rem' }}>
        {members.map((m) => (
          <li key={m.id} style={{ marginBottom: '0.5rem' }}>
            <strong>{m.name}</strong> - {m.email} {m.phone && `(${m.phone})`} [{m.status}]
          </li>
        ))}
      </ul>
    </main>
  )
}
