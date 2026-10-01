/**
 * Master Data Layanan Pengujian Laboratorium BRMP Biogen
 * Berdasarkan Daftar Tarif Layanan Resmi Kementerian Pertanian (Murni Tanpa Biaya)
 */
export const MASTER_JENIS_PENGUJIAN = [
  {
    kategori: 'Analisis SSR/RAPD',
    icon: 'dna',
    subLayanan: [
      'Isolasi DNA mini preparation',
      'Isolasi DNA large preparation',
      'Isolasi DNA dan PCR dengan satu primer (paket 6 sampel)',
      'Uji PCR dengan Penambahan per Satu primer',
      'Analisis SSR',
      'Analisis RAPD'
    ]
  },
  {
    kategori: 'Deteksi GMO',
    icon: 'shield',
    subLayanan: [
      'Isolasi DNA mini preparation',
      'Isolasi DNA large preparation',
      'Isolasi DNA dan PCR dengan satu primer (paket 6 sampel)',
      'Uji PCR dengan Penambahan per satu primer',
      'Deteksi GMO'
    ]
  },
  {
    kategori: 'Deteksi Virus secara Molekuler',
    icon: 'activity',
    subLayanan: [
      'Isolasi RNA',
      'Konvensional PCR RNA'
    ]
  },
  {
    kategori: 'Analisis Ploidi Level',
    icon: 'layers',
    isSingle: true,
    subLayanan: [
      'Analisis ploidi level'
    ]
  },
  {
    kategori: 'Uji Mutu Benih (ISTA)',
    icon: 'sprout',
    subLayanan: [
      'Uji daya kecambah benih kecil',
      'Uji daya kecambah benih besar',
      'Uji kemurnian fisik benih',
      'Uji kadar air (metode oven)',
      'Penetapan Berat 1000 butir'
    ]
  },
  {
    kategori: 'Liofilisasi',
    icon: 'snowflake',
    subLayanan: [
      'Mengering-bekukan mikroba (kelipatan 48 ampul)',
      'Mengering-bekukan sampel padat maks. 1000 gr',
      'Pemekatan sampel cair maksimal 500ml'
    ]
  },
  {
    kategori: 'Enumerasi Total Mikroba Bakteri/Cendawan',
    icon: 'disc',
    isSingle: true,
    subLayanan: [
      'Enumerasi total mikroba bakteri/cendawan'
    ]
  },
  {
    kategori: 'Deteksi Mikroba secara Molekuler (Bakteri/Cendawan)',
    icon: 'microscope',
    subLayanan: [
      'Isolasi DNA mini preparation',
      'Isolasi DNA large preparation',
      'Isolasi DNA dan PCR dengan satu primer (paket 6 sampel)',
      'Konvensional PCR DNA (primer 16S RNA dan ITS)'
    ]
  },
  {
    kategori: 'Uji Sensitivitas Bakteri',
    icon: 'flask',
    isSingle: true,
    subLayanan: [
      'Uji sensitivitas bakteri'
    ]
  },
  {
    kategori: 'Pengujian Lainnya',
    icon: 'more',
    isCustom: true,
    subLayanan: []
  }
]

export const KATEGORI_PENGUJIAN_LIST = MASTER_JENIS_PENGUJIAN.map(item => item.kategori)

// Flat list semua item parameter uji yang bisa dipilih cross-kategori
export const ALL_PARAMETERS = []
MASTER_JENIS_PENGUJIAN.forEach(cat => {
  if (cat.isSingle) {
    ALL_PARAMETERS.push({
      id: `${cat.kategori}::${cat.kategori}`,
      kategori: cat.kategori,
      nama: cat.kategori,
      isSingle: true
    })
  } else if (!cat.isCustom) {
    cat.subLayanan.forEach(sub => {
      ALL_PARAMETERS.push({
        id: `${cat.kategori}::${sub}`,
        kategori: cat.kategori,
        nama: sub,
        isSingle: false
      })
    })
  }
})

/**
 * Format pilihan parameter (cross-kategori) menjadi string terstruktur yang tersimpan di DB
 * @param {Array<string>} selectedIds - List id seperti ["Analisis SSR/RAPD::Isolasi DNA mini preparation", ...]
 * @param {string} customText - Teks tambahan jika memilih Pengujian Lainnya
 */
export function formatCrossJenisPengujian(selectedIds = [], customText = '') {
  if ((!selectedIds || selectedIds.length === 0) && !customText) {
    return ''
  }

  // Kelompokkan id terpilih berdasarkan kategori
  const grouped = {}
  selectedIds.forEach(id => {
    const [kategori, nama] = id.split('::')
    if (!grouped[kategori]) {
      grouped[kategori] = []
    }
    grouped[kategori].push(nama)
  })

  const parts = []
  
  // Urutkan sesuai urutan MASTER_JENIS_PENGUJIAN
  MASTER_JENIS_PENGUJIAN.forEach(cat => {
    if (grouped[cat.kategori]) {
      const items = grouped[cat.kategori]
      if (cat.isSingle) {
        parts.push(cat.kategori)
      } else {
        parts.push(`${cat.kategori} (${items.join(', ')})`)
      }
    }
  })

  // Tambahkan Pengujian Lainnya jika ada
  if (customText && customText.trim()) {
    parts.push(`Pengujian Lainnya: ${customText.trim()}`)
  }

  return parts.join('; ')
}

/**
 * Parse string dari database kembali menjadi list selectedIds dan customText
 * Mendukung format baru cross-kategori, format lama dengan strip (-), maupun kategori tunggal.
 */
export function parseCrossJenisPengujian(rawString) {
  if (!rawString) {
    return { selectedIds: [], customText: '' }
  }

  const selectedIds = new Set()
  let customText = ''

  // Ekstrak custom text jika ada "Pengujian Lainnya: ..."
  if (rawString.includes('Pengujian Lainnya:')) {
    const match = rawString.match(/Pengujian Lainnya:\s*([^;]+)/)
    if (match) {
      customText = match[1].trim()
    }
  }

  // Pecah berdasarkan pemisah kategori ';' jika ada
  const segments = rawString.split(';').map(s => s.trim()).filter(Boolean)

  segments.forEach(segment => {
    if (segment.startsWith('Pengujian Lainnya:')) {
      return
    }

    // Cek format: "Kategori (Sub 1, Sub 2)"
    const parenMatch = segment.match(/^([^(]+)\s*\(([^)]+)\)$/)
    if (parenMatch) {
      const catName = parenMatch[1].trim()
      const subs = parenMatch[2].split(',').map(s => s.trim()).filter(Boolean)
      
      subs.forEach(sub => {
        // Cocokkan dengan master parameter
        const found = ALL_PARAMETERS.find(p => p.kategori === catName && (p.nama.toLowerCase() === sub.toLowerCase() || p.nama.toLowerCase().includes(sub.toLowerCase())))
        if (found) {
          selectedIds.add(found.id)
        } else {
          selectedIds.add(`${catName}::${sub}`)
        }
      })
      return
    }

    // Cek format: "Kategori - Sub 1, Sub 2"
    if (segment.includes(' - ')) {
      const [catName, subsStr] = segment.split(' - ')
      const subs = subsStr.split(',').map(s => s.trim()).filter(Boolean)
      subs.forEach(sub => {
        const found = ALL_PARAMETERS.find(p => p.kategori === catName.trim() && (p.nama.toLowerCase() === sub.toLowerCase() || p.nama.toLowerCase().includes(sub.toLowerCase())))
        if (found) {
          selectedIds.add(found.id)
        } else {
          selectedIds.add(`${catName.trim()}::${sub}`)
        }
      })
      return
    }

    // Cek apakah exact match kategori tunggal atau kategori utuh
    const singleCat = ALL_PARAMETERS.find(p => p.kategori.toLowerCase() === segment.toLowerCase())
    if (singleCat) {
      selectedIds.add(singleCat.id)
      return
    }

    // Pencocokan fleksibel jika terdapat sub-nama yang terkandung
    ALL_PARAMETERS.forEach(p => {
      if (segment.toLowerCase().includes(p.nama.toLowerCase())) {
        selectedIds.add(p.id)
      }
    })
  })

  // Jika masih kosong dan bukan custom, fallback ke customText
  if (selectedIds.size === 0 && !customText && rawString !== 'Pengujian Lainnya') {
    customText = rawString
  }

  return {
    selectedIds: Array.from(selectedIds),
    customText
  }
}

// Kompatibilitas mundur
export const formatJenisPengujian = formatCrossJenisPengujian
export const parseJenisPengujian = parseCrossJenisPengujian
