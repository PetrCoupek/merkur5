<?php
/** CSV Iterator
 * based on https://gist.github.com/codeguy/6679265 from codeguy
 * modified and patched
 * @author Petr Coupek
 */ 

class CsvIterator implements \Iterator
{
    const ROW_SIZE = 0; //4096;

    /**
    * The pointer to the cvs file.
    * @var resource
    * @access protected
    */
    protected $filePointer = NULL;

    /**
    * The current element, which will
    * be returned on each iteration.
    * @var array
    * @access protected
    */
    protected $currentElement = NULL;

    /**
    * The row counter.
    * @var int
    * @access protected
    */
    protected $rowCounter = NULL;

    /**
    * The delimiter for the csv file.
    * @var str
    * @access protected
    */
    protected $delimiter = NULL;

    /**
    * This is the constructor.It try to open the csv file.The method throws an exception
    * on failure.
    *
    * @access public
    * @param str $file The csv file.
    * @param str $delimiter The delimiter.
    *
    * @throws Exception
    */
    
    protected $enclosure;
    protected $escape;
    
    public function __construct($file, $delimiter = ',',$enclosure= '"', $escape="\\")
    {
        try {
            $this->debug=false; //true;
            $this->filePointer = fopen($file, 'rb');
            $this->delimiter = $delimiter;
            $this->enclosure=$enclosure;
            $this->escape=$escape;
            /* nacti prvni radek a napln jmena sloupcu */
            
            $this->currentElement= array();
            $this->rowCounter=0;
        } catch (\Exception $e) {
            throw new \Exception('The file "' . $file . '" cannot be read.');
        }
    }

    /**
    * This method resets the file pointer.
    * this method is called at the start of the foreach loop
    * PC: it is also expected the first record to be available
    *
    * @access public
    */
    public function rewind()
    {
        if ($this->debug) echo "rewind ";
        rewind($this->filePointer);
        /* the first row contains the colument labels */
        $this->labelElement = fgetcsv($this->filePointer, self::ROW_SIZE, $this->delimiter, $this->enclosure, $this->escape);        
        $this->rowCounter = 1; /* at 0, there are column labels, skip them */
        /* do internal 'next', rewind do not call next at start */
        if ($a=fgetcsv($this->filePointer, self::ROW_SIZE, $this->delimiter, $this->enclosure, $this->escape)){
           /* nacteni dat do hashe */
           $n=count($a);
           for ($i=0;$i<$n;$i++){
              $this->currentElement[$this->labelElement[$i]] = $a[$i];
           }
           return !feof($this->filePointer);
        }   
    }

    /**
    * This method returns the current csv row as a 2 dimensional array
    *
    * @access public
    * @return array The current csv row as a 2 dimensional array
    */
    public function current()
    {   if ($this->debug) echo "current ";
        return $this->currentElement;
    }
    
    /**
    * This method returns the current row number.
    *
    * @access public
    * @return int The current row number
    */
    public function key()
    {   if ($this->debug) echo "key ";
        return $this->rowCounter;
    }
    
    /**
    * This method checks if the end of file is reached.
    *
    * @access public
    * @return boolean Returns true on EOF reached, false otherwise.
    */
    public function next()
    {   if ($this->debug) echo "next ";
        if (is_resource($this->filePointer)) {
           if ($a=fgetcsv($this->filePointer, self::ROW_SIZE, $this->delimiter, $this->enclosure, $this->escape)){
              /* nacteni dat do hashe */
              $n=count($a);
              for ($i=0;$i<$n;$i++){
                $this->currentElement[$this->labelElement[$i]] = $a[$i];
              }
              $this->rowCounter++;
              return !feof($this->filePointer);
           }              
        }

        return false;
    }
    
    /**
    * This method checks if the next row is a valid row.
    *
    * @access public
    * @return boolean If the next row is a valid row.
    */
    public function valid()
    {   if ($this->debug) echo "valid ";
        if (feof($this->filePointer)) {
            if (is_resource($this->filePointer)) {
                fclose($this->filePointer);
            }
            return false;
        }
        return true;
    }
}
 
?>
