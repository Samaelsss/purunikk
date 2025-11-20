// products.js - data untuk halaman detail, seragam dengan Produk_detail.html

const PRODUCTS = [
  {
    id: '1',
    name: 'Slingbag Purun Lala',
    price: 249000,
    category: 'Tas Selempang & Bahu Wanita',
    img: 'Product/img/Slingbag-Purun-Lala/01.webp',
    images: [
      'Product/img/Slingbag-Purun-Lala/01.webp',
      'Product/img/Slingbag-Purun-Lala/02.webp',
      'Product/img/Slingbag-Purun-Lala/03.webp',
      'Product/img/Slingbag-Purun-Lala/04.webp',
      'Product/img/Slingbag-Purun-Lala/05.webp',
      'Product/img/Slingbag-Purun-Lala/06.webp',
      'Product/img/Slingbag-Purun-Lala/07.webp',
      'Product/img/Slingbag-Purun-Lala/08.webp',
      'Product/img/Slingbag-Purun-Lala/09.webp',
      'Product/img/Slingbag-Purun-Lala/10.webp'
    ],
    description: 'Tote Bag klasik bergaya minimalis dengan bahan purun, cocok untuk kegiatan sehari-hari.',
    sold: 120,
    rating: 4,
    motifs: [
      { name: 'Bunga', img: 'Produk_detail/Img/Logo_company.jpg' },
      { name: 'Anyaman', img: 'Produk_detail/Img/Logo_company.jpg' },
      { name: 'Monokrom', img: 'Produk_detail/Img/Logo_company.jpg' },
      { name: 'Garis', img: 'Produk_detail/Img/Logo_company.jpg' },
      { name: 'Polkadot', img: 'Produk_detail/Img/Logo_company.jpg' }
    ],
    models: ['Resleting', 'Pengait'],
    // Variant pricing: model -> motif -> price
    pricing: {
      'Resleting': {
        'Bunga': 259000,
        'Anyaman': 255000,
        'Monokrom': 249000,
        'Garis': 245000,
        'Polkadot': 250000
      },
      'Pengait': {
        'Bunga': 239000,
        'Anyaman': 235000,
        'Monokrom': 229000,
        'Garis': 225000,
        'Polkadot': 230000
      }
    },
    specs: {
      'Kategori': 'Tas Selempang & Bahu Wanita',
      'Bahan': 'Purun',
      'Ukuran Produk': '10 cm × 2 cm × 15 cm',
      'Panjang Tali': '115 cm',
      'Keterangan Tambahan': [
        'Muat HP dan dompet kecil',
        'Cocok digunakan untuk jalan-jalan'
      ]
    }
  },
  {
    id: 'dompet-minimalis',
    name: 'Dompet Minimalis',
    price: 99000,
    category: 'Aksesori',
    img: 'Product/img/IMG_4091.jpg',
    images: [
      'Product/img/IMG_4091.jpg',
      'Product/img/IMG_4090.jpg',
      'Product/img/IMG_4094.jpg'
    ],
    description: 'Dompet slim untuk kartu dan sedikit uang tunai, desain tipis nyaman di saku.',
    sold: 65,
    rating: 4,
    motifs: [
      { name: 'Plain', img: 'Produk_detail/Img/Logo_company.jpg' },
      { name: 'Emboss', img: 'Produk_detail/Img/Logo_company.jpg' }
    ],
    models: ['Snap', 'Resleting'],
    pricing: {
      'Snap': {
        'Plain': 99000,
        'Emboss': 109000
      },
      'Resleting': {
        'Plain': 119000,
        'Emboss': 129000
      }
    },
    specs: {
      'Kategori': 'Aksesori',
      'Bahan': 'Kanvas + PU Trim',
      'Ukuran Produk': '10 cm × 8 cm × 1.5 cm',
      'Panjang Tali': '-',
      'Keterangan Tambahan': [
        'Muatan 6 kartu',
        'Lapisan dalam halus'
      ]
    }
  },
  {
    id: 'tas-selempang-kasual',
    name: 'Tas Selempang Kasual',
    price: 180000,
    category: 'Tas',
    img: 'Product/img/IMG_4106.jpg',
    images: [
      'Product/img/IMG_4106.jpg',
      'Product/img/IMG_4105.jpg',
      'Product/img/IMG_4104.jpg'
    ],
    description: 'Tas selempang unisex ringan, nyaman saat bepergian, tali dapat disesuaikan.',
    sold: 87,
    rating: 5,
    motifs: [
      { name: 'Klasik', img: 'Produk_detail/Img/Logo_company.jpg' },
      { name: 'Kasual', img: 'Produk_detail/Img/Logo_company.jpg' }
    ],
    models: ['Resleting'],
    pricing: {
      'Resleting': {
        'Klasik': 185000,
        'Kasual': 180000
      }
    },
    specs: {
      'Kategori': 'Tas',
      'Bahan': 'Nylon & PU',
      'Ukuran Produk': '22 cm × 18 cm × 8 cm',
      'Panjang Tali': '120 cm (adjustable)',
      'Keterangan Tambahan': [
        'Ringan',
        'Kompartemen utama + saku depan'
      ]
    }
  }
];